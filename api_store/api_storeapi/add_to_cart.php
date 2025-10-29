<?php
include 'db_aktivitas_login.php';
include 'db_config.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$customer_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['product_id'], $data['product_name'], $data['price'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$product_id = $data['product_id'];
$product_name = $data['product_name'];
$price = $data['price'];
$qty = $data['qty'] ?? 1;

try {
    // Cek apakah produk sudah ada di keranjang
    $sql_check = "SELECT id, qty FROM carts WHERE customer_id = ? AND product_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param('ii', $customer_id, $product_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        // Update qty jika produk sudah ada
        $row = $result->fetch_assoc();
        $new_qty = $row['qty'] + $qty;

        $sql_update = "UPDATE carts SET qty = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('ii', $new_qty, $row['id']);
        $stmt_update->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Kuantitas diperbarui',
            'action' => 'update'
        ]);
    } else {
        // Insert baru jika produk belum ada
        $sql_insert = "INSERT INTO carts (customer_id, product_id, product_name, price, qty) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param('iisdi', $customer_id, $product_id, $product_name, $price, $qty);
        $stmt_insert->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Produk ditambahkan',
            'action' => 'insert'
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
