<?php
// check_order.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

$conn = new mysqli("localhost", "root", "", "premium_store");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Koneksi database gagal"]);
    exit;
}

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Belum login"]);
    exit;
}

$user_id = $_SESSION['user_id'];

 // Cek apakah ada pesanan completed DAN ambil product_id dari pesanan terakhir
  $query = "SELECT COUNT(*) AS total, products FROM sales WHERE customer_id = ? AND status = 'completed' ORDER BY created_at DESC LIMIT 1";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();

$can_review = $row['total'] > 0;

$stmt->close();
$conn->close();

echo json_encode(["success" => true, "can_review" => $can_review]);
?>