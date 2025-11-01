<?php
include 'db_aktivitas_login.php';
include 'db_config.php';

header('Access-Control-Allow-Origin: http://localhost'); // atau http://127.0.0.1
header('Access-Control-Allow-Credentials: true');

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['cart' => []]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    $product_id = $input['product_id'];
    $qty = $input['qty'];
    $user_id = $_SESSION['user_id']; // pastikan user login

    $update = $conn->prepare("UPDATE cart SET qty = ? WHERE user_id = ? AND product_id = ?");
    $update->bind_param("iii", $qty, $user_id, $product_id);
    $update->execute();
    echo json_encode(["success" => true]);
    exit;
}

$customer_id = $_SESSION['user_id'];
$sql = "SELECT product_id, product_name, price, qty FROM carts WHERE customer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$cart = [];
while ($row = $result->fetch_assoc()) {
    $cart[] = $row;
}

echo json_encode(['cart' => $cart]);
?>
