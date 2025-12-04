<?php

include 'db_aktivitas_login.php';
include 'db_config.php';
include 'midtrans_config.php'; // konfigurasi Midtrans
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');


session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['cart'])) {
    echo json_encode(['error' => 'Invalid cart data']);
    exit;
}

// Ambil data dari form checkout
$customer_name = $data['customer_name'] ?? ($_SESSION['user_name'] ?? 'Customer');
$customer_email = $data['customer_email'] ?? ($_SESSION['user_email'] ?? 'customer@example.com');
$customer_phone = $data['customer_phone'] ?? ''; // Ambil dari form, fallback kosong
$note = $data['note'] ?? '';
$payment_method = $data['payment_method'] ?? 'Midtrans';

$cart = $data['cart'];
$total = 0;
$item_details = [];
foreach ($cart as $item) {
    $total += $item['price'] * $item['qty'];
    $item_details[] = [
        'id' => (string)$item['product_id'],
        'price' => (int)$item['price'],
        'quantity' => (int)$item['qty'],
        'name' => $item['product_name']
    ];
}

// Hapus item dari keranjang SEGERA setelah klik "Bayar Sekarang"
foreach ($cart as $item) {
    $sql_clear = "DELETE FROM carts WHERE customer_id = ? AND product_id = ?";
    $stmt_clear = $conn->prepare($sql_clear);
    $stmt_clear->bind_param('ii', $user_id, $item['product_id']);
    $stmt_clear->execute();
}

// Simpan order ke database (opsional: tambahkan kolom customer_name, customer_email, customer_phone jika ingin simpan detail)
$order_number = 'ORD-' . time() . rand(100, 999);
$products_json = json_encode($cart);
$sql = "INSERT INTO sales (order_number, customer_id, products, total, status, payment_method, note) VALUES (?, ?, ?, ?, 'pending', ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sisdss', $order_number, $user_id, $products_json, $total, $payment_method, $note);
$stmt->execute();
$order_id = $conn->insert_id;

// Buat data transaksi Midtrans DENGAN customer_details LENGKAP dari form
$transaction_data = [
    'transaction_details' => [
        'order_id' => $order_number,
        'gross_amount' => (int)$total
    ],
    'customer_details' => [
        'first_name' => $customer_name,
        'email' => $customer_email,
        'phone' => $customer_phone
    ],
    'item_details' => $item_details,
    'enabled_payments' => ['qris', 'credit_card', 'gopay', 'shopeepay', 'permata_va', 'bca_va', 'bni_va', 'bri_va']
];

// Buat snap token
$snap_token = createSnapToken($transaction_data);

if ($snap_token) {
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'snap_token' => $snap_token
    ]);
} else {
    echo json_encode(['error' => 'Failed to create Midtrans transaction']);
}


?>