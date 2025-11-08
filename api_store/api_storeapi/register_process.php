<?php
include 'db_config.php';  
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');
    $status = $data['status'] ?? 'active';

    if (!$name || !$email || !$password) {
        echo json_encode(['status' => 'error', 'message' => 'Nama, email, dan password harus diisi.']);
        exit;
    }

    // ✅ VALIDASI PASSWORD KUAT (minimal 8 karakter, ada huruf besar, kecil, angka, simbol)
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol.']);
        exit;
    }

    // Cek jika email sudah ada
    $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar.']);
        exit;
    }

    // Hash password dengan bcrypt
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Simpan ke database
    $stmt = $conn->prepare("INSERT INTO customers (name, email, password, status, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param('ssss', $name, $email, $hashedPassword, $status);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Registrasi berhasil.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
}
$conn->close();
?>