<?php
include 'db_aktivitas_login.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
session_start();
session_destroy();
echo json_encode(['status' => 'success', 'message' => 'Logout berhasil.']);
?>