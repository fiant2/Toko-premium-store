<?php
include 'db_aktivitas_login.php';
include 'db_config.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$customer_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['product_id'], $data['product_name'], $data['price'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$product_id = $data['product_id'];
$product_name = $data['product_name'];
$price = $data['price'];
$qty = $data['qty'] ?? 1;

try {
    // Langkah 1: Clear semua item di keranjang user ini
    $sql_clear = "DELETE FROM carts WHERE customer_id = ?";
    $stmt_clear = $conn->prepare($sql_clear);
    $stmt_clear->bind_param('i', $customer_id);
    $stmt_clear->execute();

    // Langkah 2: Tambahkan produk baru (hanya 1 item)
    $sql_insert = "INSERT INTO carts (customer_id, product_id, product_name, price, qty) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param('iisdi', $customer_id, $product_id, $product_name, $price, $qty);
    $stmt_insert->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Produk siap untuk checkout langsung'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>