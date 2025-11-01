<?php
include 'db_aktivitas_login.php';
include 'db_config.php';

header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$customer_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$product_ids = $data['product_ids'] ?? []; // bisa array (hapus banyak)
$product_id  = $data['product_id'] ?? null; // atau satuan

// --- Hapus banyak produk sekaligus
if (!empty($product_ids) && is_array($product_ids)) {
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $types = str_repeat('i', count($product_ids) + 1);
    $sql = "DELETE FROM carts WHERE customer_id = ? AND product_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $params = array_merge([$customer_id], $product_ids);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    echo json_encode(['success' => true, 'deleted' => count($product_ids)]);
    exit;
}

// --- Hapus satu produk (default)
if ($product_id) {
    $sql = "DELETE FROM carts WHERE customer_id = ? AND product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $customer_id, $product_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'deleted' => 1]);
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>
