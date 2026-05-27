<?php
// config/cors.php  –  SIAKAD v2
// CORS, security headers, dan helper functions

/* ────────────────────────────────────────────
   CORS Headers
   Untuk produksi: ganti '*' dengan domain spesifik
   Contoh: 'https://siakad.universitasku.ac.id'
──────────────────────────────────────────── */
$allowedOrigin = '*'; // ganti saat deploy ke produksi

header("Access-Control-Allow-Origin: $allowedOrigin");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

/* ── Security Headers ── */
header('X-Content-Type-Options: nosniff');       // cegah MIME sniffing
header('X-Frame-Options: DENY');                 // cegah clickjacking
header('X-XSS-Protection: 1; mode=block');       // proteksi XSS browser lama
header('Referrer-Policy: strict-origin');

/* ── Preflight (OPTIONS) ── */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

/* ────────────────────────────────────────────
   Helper: kirim response JSON dan hentikan eksekusi
──────────────────────────────────────────── */
function sendResponse(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

/* ────────────────────────────────────────────
   Helper: ambil & decode body JSON dari request
──────────────────────────────────────────── */
function getRequestBody(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) return [];

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/* ────────────────────────────────────────────
   Helper: cek apakah user sudah login
   Bisa dipanggil dari endpoint manapun
──────────────────────────────────────────── */
function requireLogin(string ...$allowedRoles): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['logged_in'])) {
        sendResponse(['error' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
    }

    if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles, true)) {
        sendResponse([
            'error' => 'Anda tidak memiliki izin untuk mengakses fitur ini.'
        ], 403);
    }
}