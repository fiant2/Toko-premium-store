<?php

date_default_timezone_set('Asia/Jakarta');

$mysqli = new mysqli('localhost', 'root', '', 'premium_store'); // Ganti sesuai konfigurasi Anda

function db_session_open($save_path, $session_name) { 
    return true; 
}

function db_session_close() { 
    return true; 
}

function db_session_read($id) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT data FROM sessions WHERE id = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $stmt->bind_result($data);
    if ($stmt->fetch()) {
        return $data;
    }
    return '';
}

function db_session_write($id, $data) {
    global $mysqli;
    // Simpan dalam UTC
    $current_time = date('Y-m-d H:i:s');
    $stmt = $mysqli->prepare("REPLACE INTO sessions (id, data, timestamp) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $id, $data, $current_time);
    return $stmt->execute();
}

function db_session_destroy($id) {
    global $mysqli;
    $stmt = $mysqli->prepare("DELETE FROM sessions WHERE id = ?");
    $stmt->bind_param('s', $id);
    return $stmt->execute();
}

function db_session_gc($maxlifetime) {
    global $mysqli;
    // Hitung batas waktu dalam UTC
    $old_time = date('Y-m-d H:i:s', time() - $maxlifetime);
    $stmt = $mysqli->prepare("DELETE FROM sessions WHERE timestamp < ?");
    $stmt->bind_param('s', $old_time);
    return $stmt->execute();
}

session_set_save_handler(
    'db_session_open',
    'db_session_close',
    'db_session_read',
    'db_session_write',
    'db_session_destroy',
    'db_session_gc'
);
register_shutdown_function('session_write_close');
?>