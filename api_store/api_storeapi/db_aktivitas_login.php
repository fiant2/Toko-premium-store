<?php
// db_aktivitas_login.php
$mysqli = new mysqli('localhost', 'root', '', 'premium_store'); // ganti user/password sesuai XAMPP kamu

function db_session_open($save_path, $session_name) { return true; }
function db_session_close() { return true; }
function db_session_read($id) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT data FROM sessions WHERE id = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $stmt->bind_result($data);
    if ($stmt->fetch()) return $data;
    return '';
}
function db_session_write($id, $data) {
    global $mysqli;
    $time = time();
    $stmt = $mysqli->prepare("REPLACE INTO sessions (id, data, timestamp) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $id, $data, $time);
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
    $old = time() - $maxlifetime;
    $stmt = $mysqli->prepare("DELETE FROM sessions WHERE timestamp < ?");
    $stmt->bind_param('i', $old);
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