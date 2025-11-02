<?php
include 'db_aktivitas_login.php';
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'User belum login']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

$mysqli = new mysqli("localhost", "root", "", "premium_store"); // ganti db_toko

if ($action == 'add') {
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $qty = $_POST['qty'] ?? 1;

    if (!$name) {
        echo json_encode(['success'=>false,'message'=>'Produk tidak valid']);
        exit;
    }

    // cek apakah sudah ada
    $stmt = $mysqli->prepare("SELECT id, qty FROM cart WHERE user_id=? AND product_name=?");
    $stmt->bind_param("ss",$user_id,$name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $new_qty = $row['qty'] + $qty;
        $update = $mysqli->prepare("UPDATE cart SET qty=? WHERE id=?");
        $update->bind_param("ii",$new_qty,$row['id']);
        $update->execute();
    } else {
        $insert = $mysqli->prepare("INSERT INTO cart (user_id, product_name, price, qty) VALUES (?,?,?,?)");
        $insert->bind_param("ssii",$user_id,$name,$price,$qty);
        $insert->execute();
    }
    echo json_encode(['success'=>true]);
    exit;
}

if ($action == 'get') {
    $stmt = $mysqli->prepare("SELECT id, product_name AS name, price, qty FROM cart WHERE user_id=?");
    $stmt->bind_param("s",$user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart = [];
    while($row=$result->fetch_assoc()){
        $cart[]=$row;
    }
    echo json_encode($cart);
    exit;
}

if ($action == 'remove') {
    $id = $_POST['id'] ?? '';
    if (!$id) {
        echo json_encode(['success'=>false,'message'=>'ID tidak valid']);
        exit;
    }
    $stmt = $mysqli->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
    $stmt->bind_param("is",$id,$user_id);
    $stmt->execute();
    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Action tidak valid']);
