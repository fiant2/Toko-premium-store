<?php
// Use DB-backed session handler for consistency with API
require_once 'api_store/api_storeapi/db_aktivitas_login.php';
session_start();
include 'api_store/api_storeapi/db_config.php';  // Koneksi database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Query cek admin dari database
    $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();  // <-- Tambahkan ini! Ambil data admin dari hasil query

    // Debug: Selalu tampilkan saat POST (untuk troubleshooting)
    echo "Debug: Username input: '$username'<br>";
    echo "Debug: Password input: (tersembunyi)<br>";
    if ($admin) {
        echo "Debug: Admin ditemukan di DB dengan ID: " . $admin['id'] . "<br>";
        if (password_verify($password, $admin['password'])) {
            echo "Debug: Password cocok! Redirecting...<br>";
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $username;
            header('Location: admin.php');  // Redirect ke dashboard admin
            exit;
        } else {
            echo "Debug: Password TIDAK cocok!<br>";
            $error = 'Username atau password salah!';
        }
    } else {
        echo "Debug: Admin TIDAK ditemukan di DB!<br>";
        $error = 'Username atau password salah!';
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <style>
        /* Import font dari admin.css */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        /* Reset dan base style seperti admin.css */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); /* Background serupa admin.css tapi dengan gradient */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        /* Container login seperti card di admin.css */
        .login-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px); /* Efek hover seperti card di admin.css */
        }
        
        /* Header login */
        .login-header h2 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1.5rem;
        }
        
        .login-header .icon {
            font-size: 3rem;
            color: #10b981; /* Warna hijau seperti admin.css */
            margin-bottom: 1rem;
        }
        
        /* Form styles seperti di admin.css */
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #10b981; /* Focus hijau seperti admin.css */
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        /* Button seperti btn-primary di admin.css */
        .btn-primary {
            background-color: #10b981;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
            font-size: 1rem;
        }
        
        .btn-primary:hover {
            background-color: #059669; /* Hover lebih gelap */
        }
        
        /* Error message */
        .error {
            color: #dc2626; /* Merah seperti negative di admin.css */
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        /* Link kembali */
        .back-link {
            margin-top: 1.5rem;
            text-align: center;
        }
        
        .back-link a {
            color: #10b981;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #059669;
        }
        
        /* Responsive seperti admin.css */
        @media (max-width: 768px) {
            .login-container {
                padding: 1.5rem;
                max-width: 100%;
            }
            
            .login-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="icon">🔐</div> <!-- Icon kunci untuk login -->
            <h2>Login Admin</h2>
        </div>
        
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan password" autocomplete="current-password" required>
            </div>
            
            <button type="submit" class="btn-primary">Login</button>
        </form>
        
        <div class="back-link">
            <a href="indexuser.html">← Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>