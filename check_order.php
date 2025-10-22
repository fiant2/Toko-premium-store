<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = new mysqli("localhost", "root", "", "premium_store");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Koneksi database gagal"]);
    exit;
}

// ⚠️ Pastikan login_user.html dan login_process.php menyimpan $_SESSION['user_id']
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Belum login"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek apakah user punya pesanan yang status-nya 'selesai'
$query = "SELECT COUNT(*) AS total FROM sales WHERE customer_id = ? AND status = 'selesai'";
// check_order.php (lanjutan setelah baris 26)

$user_id = $_SESSION['user_id'];

// Cek apakah user punya pesanan yang status-nya 'selesai'
$query = "SELECT COUNT(*) AS total FROM sales WHERE customer_id = ? AND status = 'selesai'";

// KODE BARU DIMULAI DI SINI
$stmt = $conn->prepare($query); // <-- KESALAHAN AWAL DIPERBAIKI DI SINI
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$can_review = $row['total'] > 0;

$stmt->close();
$conn->close();

echo json_encode(["success" => true, "can_review" => $can_review]);

// TUTUP TAG PHP HANYA JIKA ADA KODE HTML/LAIN DI BAWAHNYA
// Untuk API murni seperti ini, disarankan JANGAN MENUTUP tag 