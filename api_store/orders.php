<?php
require_once 'db_config.php';
header("Content-Type: application/json");
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Jika ada ID => ambil pesanan tertentu
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM orders WHERE id = $id";
        } else {
            $sql = "SELECT * FROM orders ORDER BY tanggal DESC";
        }
        $result = $conn->query($sql);
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        echo json_encode($orders);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $name = $conn->real_escape_string($data['customer_name']);
        $email = $conn->real_escape_string($data['customer_email']);
        $product = $conn->real_escape_string($data['product_name']);
        $total = floatval($data['total']);
        $status = $conn->real_escape_string($data['status']);

        $sql = "INSERT INTO orders (customer_name, customer_email, product_name, total, status)
                VALUES ('$name', '$email', '$product', $total, '$status')";
        if ($conn->query($sql)) {
            echo json_encode(["status" => "success", "message" => "Pesanan berhasil ditambahkan"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) { echo json_encode(["error" => "ID tidak ditemukan"]); exit; }
        $id = intval($_GET['id']);
        $data = json_decode(file_get_contents("php://input"), true);
        $status = $conn->real_escape_string($data['status']);
        $sql = "UPDATE orders SET status='$status' WHERE id=$id";
        if ($conn->query($sql)) {
            echo json_encode(["status" => "success", "message" => "Status pesanan diperbarui"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) { echo json_encode(["error" => "ID tidak ditemukan"]); exit; }
        $id = intval($_GET['id']);
        $sql = "DELETE FROM orders WHERE id=$id";
        if ($conn->query($sql)) {
            echo json_encode(["status" => "success", "message" => "Pesanan dihapus"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        break;

    default:
        echo json_encode(["error" => "Metode tidak didukung"]);
}

$conn->close();
?>
