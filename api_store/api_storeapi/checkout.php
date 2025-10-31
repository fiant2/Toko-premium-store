<?php
include 'db_aktivitas_login.php';
include 'db_config.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$customer_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['cart'])) {
    echo json_encode(['error' => 'Invalid cart data']);
    exit;
}

$cart = $data['cart'];
$total = 0;
$products_json = json_encode($cart);

foreach ($cart as $item) {
    $total += $item['price'] * $item['qty'];
}

// Simpan ke sales
$sql = "INSERT INTO sales (customer_id, products, total, status, payment_method, note) VALUES (?, ?, ?, 'pending', 'QRIS', 'Checkout via web')";
$stmt = $conn->prepare($sql);
$stmt->bind_param('isd', $customer_id, $products_json, $total);
$stmt->execute();

// Hapus keranjang setelah checkout
$sql_clear = "DELETE FROM carts WHERE customer_id = ?";
$stmt_clear = $conn->prepare($sql_clear);
$stmt_clear->bind_param('i', $customer_id);
$stmt_clear->execute();

echo json_encode(['success' => true, 'order_id' => $conn->insert_id]);
?>
