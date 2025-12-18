<?php
// api_storeapi/reviews.php
require_once 'env_loader.php';
loadEnv(__DIR__ . '/.env');
include 'db_aktivitas_login.php';
session_start();
header('Content-Type: application/json');
// CORS: allow exact origin and credentials
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost';
$allowed_origin = (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) ? $origin : 'http://localhost';
header("Access-Control-Allow-Origin: $allowed_origin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// --- Handle admin update (approve/reject) early ---
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // require admin session
    if (!isset($_SESSION['admin_id'])) {
        // Log cookies and session for debugging
        file_put_contents(__DIR__ . '/reviews_debug.log', date('[Y-m-d H:i:s] ') . "PUT without admin session. COOKIE=" . json_encode($_COOKIE) . " SESSION=" . json_encode($_SESSION) . "\n", FILE_APPEND);
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin belum login']);
        exit;
    }

    // read raw input
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];
    $id = isset($data['id']) ? intval($data['id']) : 0;
    $newStatus = $data['status'] ?? '';
    if (!$id || !in_array($newStatus, ['approved', 'pending', 'rejected'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        exit;
    }

    $conn_upd = new mysqli("localhost", "root", "", "premium_store");
    if ($conn_upd->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB connection error']);
        exit;
    }

    $u = $conn_upd->prepare("UPDATE reviews SET status = ? WHERE id = ?");
    $u->bind_param('si', $newStatus, $id);
    $ok = $u->execute();
    $u->close();
    $conn_upd->close();

    echo json_encode(['success' => (bool)$ok]);
    exit;
}

$conn = new mysqli("localhost", "root", "", "premium_store");
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$status = $_GET['status'] ?? 'approved';

if (!in_array($status, ['approved', 'pending'])) {
    echo json_encode([]);
    exit;
}

$query = "
    SELECT 
        r.id,
        r.product_id,
        r.user_name, 
        r.comment, 
        r.rating, 
        p.name AS product_name 
    FROM reviews r 
    LEFT JOIN products p ON r.product_id = p.id 
    WHERE r.status = ? 
    ORDER BY r.id DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $status);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = [
        'id'            => (int)$row['id'],
        'product_id'    => (int)$row['product_id'],
        'user_name'     => $row['user_name'],
        'comment'       => $row['comment'],
        'rating'        => (int)$row['rating'],
        'product_name'  => $row['product_name'] ?? 'Produk Tidak Diketahui',
        // compatibility alias used in some admin scripts
        'productName'   => $row['product_name'] ?? 'Produk Tidak Diketahui'
    ];
}

echo json_encode($reviews);
$stmt->close();
$conn->close();

// PUT handling moved earlier in the file to run before GET/listing. See above implementation.

?>