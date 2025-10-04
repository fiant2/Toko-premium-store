<?php
// reviews.php - Endpoint Ulasan
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT'); 
header('Access-Control-Allow-Headers: Content-Type'); 

include 'db_config.php';

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$uri_parts = explode('/', trim($request_uri, '/'));

// Menangani permintaan PUT /api_store/api/reviews/{id}/status
if ($method == 'PUT' && end($uri_parts) == 'status' && count($uri_parts) >= 2) {
    $review_id = $uri_parts[count($uri_parts) - 2];
    $data = json_decode(file_get_contents("php://input"));
    $status = $data->status;

    if (!in_array($status, ['approved', 'rejected'])) {
        http_response_code(400);
        echo json_encode(["message" => "Status tidak valid."]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE reviews SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $review_id);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["message" => "Status ulasan ID $review_id berhasil diubah menjadi $status."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Gagal update status ulasan."]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Menangani permintaan GET/POST
switch ($method) {
    case 'GET':
        // Jika ada query status (untuk admin), atau tampilkan yang approved (untuk user)
        $status_filter = isset($_GET['status']) ? $_GET['status'] : 'approved';
        
        $sql = "SELECT r.*, p.name AS productName FROM reviews r JOIN products p ON r.product_id = p.id WHERE r.status = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $status_filter);
        $stmt->execute();
        $result = $stmt->get_result();

        $reviews = array();
        while($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        echo json_encode($reviews);
        $stmt->close();
        break;

    case 'POST':
        // User mengirim ulasan baru
        $data = json_decode(file_get_contents("php://input"));

        // SIMULASI CEK PEMBELIAN: Dalam PHP, ini harus memeriksa tabel orders
        // Karena kita belum membuat tabel orders, kita buat SIMULASI:
        // Jika user_id = 'USER-TEST-1', dianggap sudah beli.
        if ($data->userId !== 'USER-TEST-1') {
             http_response_code(403);
             echo json_encode(["status" => "error", "message" => "Anda hanya bisa memberi ulasan setelah pembelian selesai. (Simulasi: ID user tidak dikenal)"]);
             exit;
        }

        $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_name, comment, status) VALUES (?, ?, ?, 'pending')");
        // Kita simpan ID produk di database
        $product_id_int = intval(str_replace('P', '', $data->productId)); // Asumsi ID dari JS adalah 'P123'
        $stmt->bind_param("iss", $product_id_int, $data->userName, $data->comment);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Ulasan berhasil dikirim dan menunggu persetujuan admin."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Gagal mengirim ulasan: " . $conn->error]);
        }
        $stmt->close();
        break;
        
    case 'OPTIONS':
        // Handle CORS Preflight
        http_response_code(200);
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Method tidak diizinkan"]);
        break;
}

$conn->close();
?>