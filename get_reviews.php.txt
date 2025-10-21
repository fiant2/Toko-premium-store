<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "premium_store");

if ($conn->connect_error) {
    die(json_encode(["error" => "Koneksi gagal: " . $conn->connect_error]));
}

// Ambil hanya testimoni yang disetujui (approved)
$sql = "SELECT r.id, r.user_name, r.comment, r.review_date, p.name AS product_name
        FROM reviews r
        JOIN products p ON r.product_id = p.id
        WHERE r.status = 'approved'
        ORDER BY r.review_date DESC";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
$conn->close();
?>
