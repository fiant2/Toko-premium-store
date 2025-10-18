<?php
include 'db_aktivitas_login.php';
session_start();  // Mulai session untuk menyimpan status login
include 'db_config.php';
error_reporting(0);
ini_set('display_errors', 0);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');

    if (!$email || !$password) {
        echo json_encode(['status' => 'error', 'message' => 'Email dan password harus diisi.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, name, password FROM customers WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];  // Set session
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email']; 
        echo json_encode(['status' => 'success', 'message' => 'Login berhasil.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email atau password salah.']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
}
$conn->close();
?>