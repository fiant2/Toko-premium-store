<?php
// api_storeapi/review.php - Terima review dari user (status pending)
require_once 'env_loader.php';
loadEnv(__DIR__ . '/.env');
include 'db_aktivitas_login.php';
include 'db_config.php';

header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

// Convert PHP errors/exceptions to JSON responses to avoid HTML error pages
set_error_handler(function($severity, $message, $file, $line) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => "PHP error: $message in $file:$line"]);
    exit;
});
set_exception_handler(function($ex) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    exit;
});
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']]);
        exit;
    }
});

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Terima data baik form-encoded atau JSON. Be flexible when checking Content-Type.
$input = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];
} else {
    // parse form-encoded body
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        parse_str($raw, $input);
    }
}

$product_id = isset($input['product_id']) ? intval($input['product_id']) : null;
$comment = trim($input['comment'] ?? '');
$rating = isset($input['rating']) ? intval($input['rating']) : 5;

// Detailed validation with clearer messages to aid debugging
$errors = [];
if (!$product_id) $errors[] = 'product_id';
if (!is_int($rating) || $rating < 1 || $rating > 5) $errors[] = 'rating';
if ($comment === '') $errors[] = 'comment';
if (!empty($errors)) {
    $msg = 'Data ulasan tidak lengkap atau tidak valid';
    // Append which fields are problematic (non-sensitive)
    $msg .= ': ' . implode(', ', $errors);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// Security: pastikan user memang membeli produk ini (status 'completed')
$check = $conn->prepare("SELECT COUNT(*) as cnt FROM sales WHERE customer_id = ? AND status = 'completed' AND (products LIKE ? OR products LIKE ?) ");
$pattern_str = '%"product_id":"' . $conn->real_escape_string($product_id) . '"%';
$pattern_num = '%"product_id":' . $conn->real_escape_string($product_id) . '%';
$check->bind_param('iss', $user_id, $pattern_str, $pattern_num);
$check->execute();
$res = $check->get_result();
$row = $res ? $res->fetch_assoc() : ['cnt' => 0];
if ($row['cnt'] == 0) {
    echo json_encode(['success' => false, 'message' => 'Anda tidak dapat mengulas produk yang belum dibeli atau belum selesai pembayarannya.']);
    exit;
}
$check->close();

// Prevent duplicate reviews by same user
$dup = $conn->prepare("SELECT COUNT(*) AS cnt FROM reviews WHERE product_id = ? AND user_name = ?");
$dup->bind_param('is', $product_id, $user_name);
$dup->execute();
$dupRes = $dup->get_result();
$dupRow = $dupRes ? $dupRes->fetch_assoc() : ['cnt' => 0];
if ($dupRow['cnt'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Anda sudah menulis ulasan untuk produk ini.']);
    exit;
}
$dup->close();

$stmt = $conn->prepare("INSERT INTO reviews (product_id, user_name, comment, rating, status) VALUES (?, ?, ?, ?, 'pending')");
$stmt->bind_param('issi', $product_id, $user_name, $comment, $rating);
$ok = $stmt->execute();

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Ulasan berhasil dikirim. Menunggu persetujuan admin.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ulasan.']);
}

$stmt->close();
$conn->close();
?>