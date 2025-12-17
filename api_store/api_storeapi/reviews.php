<?php
// api_storeapi/reviews.php
require_once 'env_loader.php';
loadEnv(__DIR__ . '/.env');
include 'db_aktivitas_login.php';
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

// Allow admin to update review status via PUT
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

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

// --- Handle admin update (approve/reject) ---
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // require admin session
    if (!isset($_SESSION['admin_id'])) {
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

    $u = $conn->prepare("UPDATE reviews SET status = ? WHERE id = ?");
    $u->bind_param('si', $newStatus, $id);
    $ok = $u->execute();
    echo json_encode(['success' => $ok]);
    $u->close();
    $conn->close();
    exit;
}

?>