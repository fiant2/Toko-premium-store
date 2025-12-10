<?php
require_once 'env_loader.php';
loadEnv(__DIR__ . '/.env');
include 'db_config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ambil raw notification dari Midtrans
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// === LOG Notifikasi untuk melihat request dari Midtrans ===
file_put_contents('log_midtrans.txt', $input . PHP_EOL . "---------------------\n", FILE_APPEND);

// Validasi Signature Key (WAJIB untuk keamanan)
$serverKey = $_ENV['MIDTRANS_SERVER_KEY'];
$signatureKey = hash("sha512", 
    $data['order_id'] . 
    $data['status_code'] . 
    $data['gross_amount'] . 
    $serverKey
);

// Jika signature tidak cocok, tolak
if ($data['signature_key'] !== $signatureKey) {
    http_response_code(403);
    echo "Invalid signature";
    exit;
}

$order_id = $data['order_id'];
$transaction_status = $data['transaction_status'];
$payment_type = $data['payment_type'];

$status = 'pending';

// Konversi status dari Midtrans ke status toko
if ($transaction_status == 'settlement' || $transaction_status == 'capture') {
    $status = 'completed';
} elseif ($transaction_status == 'pending') {
    $status = 'pending';
} elseif ($transaction_status == 'deny' || $transaction_status == 'cancel' || $transaction_status == 'expire') {
    $status = 'refunded';
}

// Update database sales.status
$stmt = $conn->prepare("UPDATE sales SET status = ?, payment_method = ? WHERE order_number = ?");
$stmt->bind_param('sss', $status, $payment_type, $order_id);
$stmt->execute();

echo "OK"; // Wajib supaya Midtrans tidak Retry
?>
