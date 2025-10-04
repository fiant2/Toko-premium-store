<?php
// products.php - Endpoint Produk untuk User (GET)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // PENTING: Untuk mengizinkan JS (indexuser.html) mengakses

include 'db_config.php';

// Ambil semua produk yang statusnya 'published'
$sql = "SELECT id, name, category, price, description, stock_status, status, image_url FROM products WHERE status = 'published'";
$result = $conn->query($sql);

$products = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Keluarkan data dalam format JSON
echo json_encode($products);

$conn->close();
?>