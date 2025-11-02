<?php
// Debug: Log start
file_put_contents('debug_checkout.log', date('Y-m-d H:i:s') . ' - Start checkout' . PHP_EOL, FILE_APPEND);

error_reporting(E_ALL);
ini_set('display_errors', 1);  
// api_store/api_storeapi/checkout.php

// Tambahkan include ini untuk session handler custom
include 'db_aktivitas_login.php';

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

// Debug: Log koneksi
file_put_contents('debug_checkout.log', 'Koneksi DB: ' . ($conn->connect_error ? 'Gagal - ' . $conn->connect_error : 'Berhasil') . PHP_EOL, FILE_APPEND);

// Pastikan koneksi database berhasil, jika gagal, output JSON error
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $conn->connect_error]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login diperlukan']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Data JSON tidak valid']);
    exit;
}

$customer_id = $_SESSION['user_id'];
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
$total = 0.0;
$product_names = [];
foreach ($cart as $item) {
    $total += (float)$item['price'] * (int)$item['qty'];
    $product_names[] = $item['product_name'] . ' (x' . $item['qty'] . ')';
}
$products_string = implode(', ', $product_names);

// Debug: Log data
file_put_contents('debug_checkout.log', 'Total: ' . $total . ', Products: ' . $products_string . PHP_EOL, FILE_APPEND);

// Simpan ke tabel sales (sesuaikan kolom: total bukan total_amount, tambah products)
$order_number = 'INV' . date('Ymd') . rand(100, 999);
$stmt = $conn->prepare("
    INSERT INTO sales (order_number, customer_id, products, total, payment_method, note, status)
    VALUES (?, ?, ?, ?, ?, ?, 'pending')
");
if (!$stmt) {
    file_put_contents('debug_checkout.log', 'Prepare gagal: ' . $conn->error . PHP_EOL, FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Prepare statement gagal: ' . $conn->error]);
    exit;
}

// Perbaiki bind_param: s (order_number), i (customer_id), s (products), d (total), s (payment_method), s (note)
$stmt->bind_param('sisdss', $order_number, $customer_id, $products_string, $total, $payment_method, $note);
$result = $stmt->execute();
if (!$result) {
    file_put_contents('debug_checkout.log', 'Execute gagal: ' . $stmt->error . PHP_EOL, FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Execute gagal: ' . $stmt->error]);
    exit;
}

if ($stmt->affected_rows > 0) {
    // Kosongkan keranjang
    $stmt2 = $conn->prepare("DELETE FROM carts WHERE customer_id = ?");
    if (!$stmt2) {
        file_put_contents('debug_checkout.log', 'Prepare delete gagal: ' . $conn->error . PHP_EOL, FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Prepare delete gagal: ' . $conn->error]);
        exit;
    }
    $stmt2->bind_param('i', $customer_id);
    $stmt2->execute();

    file_put_contents('debug_checkout.log', 'Checkout berhasil: ' . $order_number . PHP_EOL, FILE_APPEND);
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

$stmt->close();
$stmt2->close();
$conn->close();
?>