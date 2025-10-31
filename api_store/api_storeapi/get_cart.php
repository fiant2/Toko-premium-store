<?php
include 'db_aktivitas_login.php';
include 'db_config.php';

header('Access-Control-Allow-Origin: http://localhost'); // atau http://127.0.0.1
header('Access-Control-Allow-Credentials: true');

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['cart' => []]);
    exit;
}

$customer_id = $_SESSION['user_id'];
$sql = "SELECT product_id, product_name, price, qty FROM carts WHERE customer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$cart = [];
while ($row = $result->fetch_assoc()) {
    $cart[] = $row;
}

echo json_encode(['cart' => $cart]);
?>
