<?php
// admin_products.php - Endpoint Produk untuk Admin (GET, POST, DELETE)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Untuk CORS
header('Access-Control-Allow-Methods: GET, POST, DELETE'); // Mengizinkan method ini
header('Access-Control-Allow-Headers: Content-Type'); // Mengizinkan header ini

include 'db_config.php';

// Mendapatkan method HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Tampilkan SEMUA produk untuk Admin
        $sql = "SELECT * FROM products";
        $result = $conn->query($sql);
        
        $products = array();
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        echo json_encode($products);
        break;

    case 'POST':
        // Tambah Produk Baru
        $data = json_decode(file_get_contents("php://input"));

        // Validasi data (seharusnya lebih lengkap)
        if (!isset($data->name) || !isset($data->price) || !isset($data->status)) {
            http_response_code(400);
            echo json_encode(["message" => "Nama, Harga, dan Status harus diisi."]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO products (name, category, price, description, features, stock_status, status, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsssss", $data->name, $data->category, $data->price, $data->description, $data->features, $data->stockStatus, $data->status, $data->imageUrl);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(["message" => "Produk berhasil ditambahkan.", "id" => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Gagal menambahkan produk: " . $conn->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        // Hapus Produk
        // URL yang diharapkan: admin_products.php?id=123
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID produk tidak ditemukan."]);
            exit;
        }
        $product_id = intval($_GET['id']);

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            http_response_code(200);
            echo json_encode(["message" => "Produk ID $product_id berhasil dihapus."]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Produk tidak ditemukan atau gagal dihapus."]);
        }
        $stmt->close();
        break;

        case 'PUT':
    parse_str($_SERVER['QUERY_STRING'], $params);
    $product_id = isset($params['id']) ? intval($params['id']) : 0;
    if (!$product_id) {
        http_response_code(400);
        echo json_encode(["message" => "ID produk tidak ditemukan."]);
        exit;
    }
    $data = json_decode(file_get_contents("php://input"));
    $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, description=?, features=?, stock_status=?, status=?, image_url=? WHERE id=?");
    $stmt->bind_param("ssdsssssi", $data->name, $data->category, $data->price, $data->description, $data->features, $data->stockStatus, $data->status, $data->imageUrl, $product_id);
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["message" => "Produk berhasil diupdate."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Gagal update produk: " . $conn->error]);
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