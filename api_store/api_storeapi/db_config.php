<?php
// db_config.php - Konfigurasi Database
$servername = "localhost";
$username = "root"; // Username default XAMPP
$password = "";     // Password default XAMPP
$dbname = "premium_store"; // Nama database yang kita buat

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>