<?php
include 'db_aktivitas_login.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
session_start();
if (isset($_SESSION['user_id'])) {
echo json_encode([
        'logged_in' => true,
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'] ?? '',
        'user_email' => $_SESSION['user_email'] ?? ''
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}