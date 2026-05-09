<?php
// api/login.php
// Endpoint login admin menggunakan session PHP

require_once '../config/cors.php';
require_once '../config/database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['error' => 'Method tidak didukung'], 405);
}

$body = getRequestBody();
if (empty($body['username']) || empty($body['password'])) {
    sendResponse(['error' => 'Username dan password wajib diisi'], 400);
}

$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
$stmt->bind_param('s', $body['username']);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Untuk testing: password default = "admin123"
// password_verify untuk hash bcrypt, atau cek plain text untuk kemudahan dev
if ($admin && $password === $admin['password']) {
    $_SESSION['admin_id']   = $admin['id'];
    $_SESSION['admin_nama'] = $admin['nama'];
    sendResponse([
        'success' => true,
        'message' => 'Login berhasil',
        'admin'   => ['id' => $admin['id'], 'nama' => $admin['nama'], 'username' => $admin['username']]
    ]);
} else {
    sendResponse(['error' => 'Username atau password salah'], 401);
}

$conn->close();
