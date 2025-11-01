<?php
include 'db_config.php';
include 'db_aktivitas_login.php';

header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? null;
$new_qty = $data['qty'] ?? null;
$customer_id = $_SESSION['user_id'];

if (!$product_id || !$new_qty || $new_qty < 1) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

$sql = "UPDATE carts SET qty = ? WHERE customer_id = ? AND product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $new_qty, $customer_id, $product_id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?>
