<?php
include 'db_config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login diperlukan']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$customer_id = $_SESSION['user_id'];

// Ambil cart
$sql = "SELECT * FROM carts WHERE customer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$items = [];
while ($row = $result->fetch_assoc()) {
    $subtotal = $row['price'] * $row['qty'];
    $total += $subtotal;
    $items[] = $row;
}

// Simpan ke sales
foreach ($items as $item) {
    $sql = "INSERT INTO sales (customer_id, product_id, product_name, price, qty, total) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt2 = $conn->prepare($sql);
    $item_total = $item['price'] * $item['qty'];
    $stmt2->bind_param('iisdid', $customer_id, $item['product_id'], $item['product_name'], $item['price'], $item['qty'], $item_total);
    $stmt2->execute();
}

// Kosongkan cart
$conn->query("DELETE FROM carts WHERE customer_id = $customer_id");

echo json_encode(['success' => true]);
?>