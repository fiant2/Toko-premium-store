<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "premium_store");

$order_id = $_GET['order_id'] ?? 0;
$sql = "SELECT status FROM sales WHERE id = ? AND status = 'paid'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode(['paid' => $result->num_rows > 0]);
?>