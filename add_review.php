
<?php

header('Content-Type: application/json');
session_start();
include 'db_config.php'; // PDO connection

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login dulu!']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = (int)$data['product_id'];
$comment = trim($data['comment']);
$rating = (int)($data['rating'] ?? 5);
$user_name = $_SESSION['user_name'] ?? 'User';

if (empty($comment) || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Isi ulasan & rating valid!']);
    exit;
}

// Prevent duplicate review by same user for same product
$dup = $pdo->prepare("SELECT COUNT(*) as cnt FROM reviews WHERE product_id = ? AND user_name = ?");
$dup->execute([$product_id, $user_name]);
$cntRow = $dup->fetch(PDO::FETCH_ASSOC);
if ($cntRow && $cntRow['cnt'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Anda sudah mengulas produk ini sebelumnya.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_name, comment, rating, status) VALUES (?, ?, ?, ?, 'pending')");
$success = $stmt->execute([$product_id, $user_name, $comment, $rating]);

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Ulasan terkirim! Menunggu approve admin ⭐' : 'Gagal kirim!'
]);
?>