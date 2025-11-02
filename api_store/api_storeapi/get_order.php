<?php
// api/get_order.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {  // Diubah dari $_SESSION['customer_id']
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$customer_id = $_SESSION['user_id'];  // Diubah dari $_SESSION['customer_id']

// Ambil order terakhir (atau sesuai logika)
$stmt = $conn->prepare("
    SELECT s.order_number, s.total_amount, s.status, s.created_at
    FROM sales s
    WHERE s.customer_id = ?
    ORDER BY s.created_at DESC LIMIT 1
");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if ($order) {
    echo json_encode([
        'success' => true,
        'order' => [
            'orderId' => $order['order_number'],
            'amount' => $order['total_amount'],
            'status' => $order['status'],
            'timestamp' => strtotime($order['created_at']) * 1000
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
}
?>