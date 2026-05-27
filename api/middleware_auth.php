<?php
// api/middleware_auth.php

session_start();

// Jika user belum login
if (empty($_SESSION['logged_in'])) {
    require_once '../config/cors.php'; // Pastikan format balasan tetap JSON
    http_response_code(401);
    
    // Pastikan pakai format 'success' dan 'message' agar seragam dengan frontend
    echo json_encode([
        'success' => false,
        'error'   => 'Akses ditolak. Silakan login terlebih dahulu.', // tetap sedia error untuk jaga-jaga
        'message' => 'Akses ditolak. Silakan login terlebih dahulu.'
    ]);
    exit; // Hentikan script di sini, file di bawahnya tidak akan dieksekusi
}
?>