<?php
include 'db_config.php';

$json = file_get_contents('php://input');
$notification = json_decode($json);

if ($notification->transaction_status == 'settlement') {
    $order_number = $notification->order_id;
    $sql = "UPDATE sales SET status = 'completed' WHERE order_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $order_number);
    $stmt->execute();
} elseif ($notification->transaction_status == 'pending') {
    // Status pending
} elseif ($notification->transaction_status == 'deny' || $notification->transaction_status == 'cancel' || $notification->transaction_status == 'expire') {
    $order_number = $notification->order_id;
    $sql = "UPDATE sales SET status = 'refunded' WHERE order_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $order_number);
    $stmt->execute();
}

echo 'OK';
?>