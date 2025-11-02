<?php
// api_storeapi/reviews.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

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
        'product_name'  => $row['product_name'] ?? 'Produk Tidak Diketahui'
    ];
}

echo json_encode($reviews);
$stmt->close();
$conn->close();
?>