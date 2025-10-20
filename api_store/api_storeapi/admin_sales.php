<?php
// admin_sales.php
// API CRUD untuk tabel sales (orders)
include 'db_config.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'GET') {
    // get by id
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT s.*, c.name AS customer_name, c.email AS customer_email FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = ?");
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        echo json_encode($row ?: []);
        exit;
    }
    // get by userId (customer_id)
    if (isset($_GET['userId'])) {
        $userId = intval($_GET['userId']);
        $stmt = $conn->prepare("SELECT s.*, c.name AS customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.customer_id = ? ORDER BY s.created_at DESC");
        $stmt->bind_param('i',$userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = []; while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode($rows);
        exit;
    }
    // default: list all
$stmt = $conn->prepare("
        SELECT 
            s.id, 
            s.order_number, 
            s.customer_id, 
            s.products, 
            s.total, 
            s.status, 
            s.payment_method, 
            s.note, 
            s.created_at
        FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.id
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        // Pastikan data aman & lengkap
        $rows[] = [
            'id' => $r['id'],
            'order_number' => $r['order_number'],
            'customer_id' => $r['customer_id'],
            'products' => $r['products'],
            'total' => $r['total'],
            'status' => $r['status'],
            'payment_method' => $r['payment_method'],
            'note' => $r['note'],
            'created_at' => $r['created_at']
        ];
    }

    echo json_encode($rows);
    exit;
}

if ($method === 'POST') {
    // create sale. if customer_id provided -> use it, else NULL
    $order_number = trim($input['order_number'] ?? ('ORD-'.time()));
    $customer_id = isset($input['customer_id']) && $input['customer_id'] !== '' ? intval($input['customer_id']) : null;
    $products = isset($input['products']) ? json_encode($input['products']) : json_encode([]);
    $total = floatval($input['total'] ?? 0);
    $status = in_array($input['status'] ?? 'pending', ['pending','processing','completed','refunded']) ? $input['status'] : 'pending';
    $payment_method = $input['payment_method'] ?? null;
    $note = $input['note'] ?? null;

    if ($customer_id === null) {
        $stmt = $conn->prepare("INSERT INTO sales (order_number, customer_id, products, total, status, payment_method, note, created_at) VALUES (?, NULL, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('sdsds', $order_number, $products, $total, $status, $payment_method, $note);
        // NOTE: older mysqli might not accept that param list; fallback below if fails
    } else {
        $stmt = $conn->prepare("INSERT INTO sales (order_number, customer_id, products, total, status, payment_method, note, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('sisdsss', $order_number, $customer_id, $products, $total, $status, $payment_method, $note);
    }

    // Some environments may need simpler binding. Try execute and fallback if fails.
    if ($stmt->execute()) {
        echo json_encode(['status'=>'success','id'=>$stmt->insert_id,'order_number'=>$order_number]);
    } else {
        // fallback: use query with proper escaping
        $on = $conn->real_escape_string($order_number);
        $pr = $conn->real_escape_string($products);
        $pm = $payment_method ? $conn->real_escape_string($payment_method) : null;
        $nt = $note ? $conn->real_escape_string($note) : null;
        if ($customer_id === null) {
            $sql = "INSERT INTO sales (order_number, customer_id, products, total, status, payment_method, note, created_at) VALUES ('$on', NULL, '$pr', $total, '$status', ".($pm? "'$pm'": "NULL").", ".($nt? "'$nt'":"NULL").", NOW())";
        } else {
            $sql = "INSERT INTO sales (order_number, customer_id, products, total, status, payment_method, note, created_at) VALUES ('$on', $customer_id, '$pr', $total, '$status', ".($pm? "'$pm'": "NULL").", ".($nt? "'$nt'":"NULL").", NOW())";
        }
        if ($conn->query($sql)) echo json_encode(['status'=>'success','id'=>$conn->insert_id,'order_number'=>$order_number]);
        else { http_response_code(500); echo json_encode(['status'=>'error','message'=>$conn->error]); }
    }
    exit;
}

if ($method === 'PUT') {
    // update order by id
    $id = isset($_GET['id']) ? intval($_GET['id']) : intval($input['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'ID diperlukan']); exit; }

    $fields=[]; $params=[]; $types='';
    if (isset($input['status'])) { $fields[]='status=?'; $params[]=$input['status']; $types.='s'; }
    if (isset($input['note'])) { $fields[]='note=?'; $params[]=$input['note']; $types.='s'; }
    if (isset($input['payment_method'])) { $fields[]='payment_method=?'; $params[]=$input['payment_method']; $types.='s'; }
    if (isset($input['total'])) { $fields[]='total=?'; $params[]=$input['total']; $types.='d'; }
    if (isset($input['products'])) { $fields[]='products=?'; $params[]=json_encode($input['products']); $types.='s'; }
    if (isset($input['customer_id'])) { $fields[]='customer_id=?'; $params[]=intval($input['customer_id']); $types.='i'; }

    if (count($fields) === 0) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Tidak ada field untuk diupdate']); exit; }

    $sql = "UPDATE sales SET ".implode(',',$fields)." WHERE id=?";
    $params[] = $id; $types .= 'i';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) echo json_encode(['status'=>'success']);
    else { http_response_code(500); echo json_encode(['status'=>'error','message'=>$stmt->error]); }
    exit;
}

if ($method === 'DELETE') {
    if (!isset($_GET['id'])) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'ID diperlukan']); exit; }
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) echo json_encode(['status'=>'success']);
    else { http_response_code(500); echo json_encode(['status'=>'error','message'=>$stmt->error]); }
    exit;
}

http_response_code(405);
echo json_encode(['status'=>'error','message'=>'Method not allowed']);
