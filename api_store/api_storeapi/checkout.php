<?php
// api_store/api_storeapi/checkout.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

require_once 'db_config.php';

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login diperlukan']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$customer_id = $_SESSION['customer_id'];
$customer_name = $data['customer_name'] ?? '';
$customer_email = $data['customer_email'] ?? '';
$note = $data['note'] ?? '';
$payment_method = $data['payment_method'] ?? 'QRIS';
$cart = $data['cart'] ?? [];

if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Keranjang kosong']);
    exit;
}

// Hitung total
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['qty'];
}

// Simpan ke tabel sales
$order_number = 'INV' . date('Ymd') . rand(100, 999);
$stmt = $conn->prepare("
    INSERT INTO sales (order_number, customer_id, total_amount, payment_method, note, status)
    VALUES (?, ?, ?, ?, ?, 'pending')
");
$stmt->bind_param('sidss', $order_number, $customer_id, $total, $payment_method, $note);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // Kosongkan keranjang
    $stmt2 = $conn->prepare("DELETE FROM carts WHERE customer_id = ?");
    $stmt2->bind_param('i', $customer_id);
    $stmt2->execute();

    echo json_encode([
        'success' => true,
        'order' => [
            'orderId' => $order_number,
            'amount' => $total,
            'timestamp' => time() * 1000
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal simpan pesanan']);
}
?>