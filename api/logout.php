<?php
// api/logout.php  –  SIAKAD v2
// Hancurkan session user

require_once '../config/cors.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['error' => 'Method tidak didukung'], 405);
}

// Kosongkan semua data session
$_SESSION = [];

// Hapus cookie session jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

sendResponse([
    'success' => true,
    'message' => 'Logout berhasil. Sampai jumpa!'
]);