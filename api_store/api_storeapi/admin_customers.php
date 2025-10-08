<?php
// admin_customers.php
// API CRUD untuk tabel customers
include 'db_config.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT id, name, email, phone, status, created_at FROM customers WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        echo json_encode($res ?: []);
        exit;
    }
    // optional search q
    $q = isset($_GET['q']) ? '%' . $conn->real_escape_string($_GET['q']) . '%' : '%';
    $stmt = $conn->prepare("SELECT id, name, email, phone, status, created_at FROM customers WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $stmt->bind_param('ss', $q, $q);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    echo json_encode($rows);
    exit;
}

if ($method === 'POST') {
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $status = in_array($input['status'] ?? 'active', ['active','inactive','banned']) ? $input['status'] : 'active';

    if (!$name || !$email) {
        http_response_code(400);
        echo json_encode(['status'=>'error','message'=>'Nama dan email wajib diisi.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, status, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param('ssss', $name, $email, $phone, $status);
    if ($stmt->execute()) echo json_encode(['status'=>'success','id'=>$stmt->insert_id]);
    else { http_response_code(500); echo json_encode(['status'=>'error','message'=>$stmt->error]); }
    exit;
}

if ($method === 'PUT') {
    // update by id (either ?id= or id in body)
    $id = isset($_GET['id']) ? intval($_GET['id']) : intval($input['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'ID diperlukan']); exit; }

    $fields = []; $params = []; $types = '';
    if (isset($input['name'])) { $fields[] = 'name=?'; $params[] = $input['name']; $types .= 's'; }
    if (isset($input['email'])) { $fields[] = 'email=?'; $params[] = $input['email']; $types .= 's'; }
    if (isset($input['phone'])) { $fields[] = 'phone=?'; $params[] = $input['phone']; $types .= 's'; }
    if (isset($input['status'])) { $fields[] = 'status=?'; $params[] = $input['status']; $types .= 's'; }

    if (count($fields) === 0) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Tidak ada field untuk diupdate.']); exit; }

    $sql = "UPDATE customers SET ".implode(',', $fields)." WHERE id = ?";
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
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) echo json_encode(['status'=>'success']);
    else { http_response_code(500); echo json_encode(['status'=>'error','message'=>$stmt->error]); }
    exit;
}

http_response_code(405);
echo json_encode(['status'=>'error','message'=>'Method not allowed']);
