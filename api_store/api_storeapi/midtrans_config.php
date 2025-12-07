<?php
// Konfigurasi Midtrans (key Sandbox Anda)
$server_key = $_ENV['MIDTRANS_SERVER_KEY'] ?? 'YOUR_SANDBOX_SERVER_KEY'; // Fallback jika .env tidak ada
$client_key = $_ENV['MIDTRANS_CLIENT_KEY'] ?? 'YOUR_SANDBOX_CLIENT_KEY';
$is_production = ($_ENV['MIDTRANS_IS_PRODUCTION'] ?? 'false') === 'true';
$midtrans_url = $is_production ? 'https://api.midtrans.com/snap/v1/transactions' : 'https://api.sandbox.midtrans.com/snap/v1/transactions';

// Fungsi untuk membuat Snap Token dengan cURL
function createSnapToken($transaction_data) {
    global $server_key, $midtrans_url;
    
    $json_data = json_encode($transaction_data);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $midtrans_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($server_key . ':')
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Untuk testing, disable SSL verify (aktifkan di production)
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Debugging: Log ke file (hapus setelah fix)
    file_put_contents('midtrans_debug.log', "HTTP Code: $http_code\nResponse: $response\nError: $error\nServer Key Used: $server_key\nData: $json_data\n\n", FILE_APPEND);
    if ($http_code == 201) {
        $result = json_decode($response, true);
        return $result['token'] ?? null; // Snap token
    } else {
        return null; // Error
    }
}
?>