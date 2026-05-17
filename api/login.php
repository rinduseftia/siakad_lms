<?php
// api/login.php  –  SIAKAD v2
// Mendukung login: admin | mahasiswa | dosen
// via tabel `users` (central auth)

require_once '../config/cors.php';
require_once '../config/database.php';

session_start();

/* ────────────────────────────────────────────────
   RATE LIMIT  –  maks 5 percobaan per 15 menit
──────────────────────────────────────────────── */
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_ts']       = time();
}

// Reset timer setelah 15 menit
if (time() - $_SESSION['login_ts'] > 900) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_ts']       = time();
}

if ($_SESSION['login_attempts'] >= 5) {
    sendResponse([
        'error' => 'Terlalu banyak percobaan login. Tunggu 15 menit.'
    ], 429);
}

/* ────────────────────────────────────────────────
   VALIDASI METHOD & INPUT
──────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['error' => 'Method tidak didukung'], 405);
}

$body     = getRequestBody();
$username = trim($body['username'] ?? '');
$password = $body['password'] ?? '';       

if ($username === '' || $password === '') {
    sendResponse(['error' => 'Username dan password wajib diisi'], 400);
}

// Sanitasi dasar (prepared statement sudah aman, ini untuk output)
$username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

/* ────────────────────────────────────────────────
   CEK USERS TABLE
──────────────────────────────────────────────── */
$conn = getConnection();

$stmt = $conn->prepare(
    "SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1"
);
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Gagal login
if (!$user || $password !== $user['password']) {
    $_SESSION['login_attempts']++;
    $sisa = 5 - $_SESSION['login_attempts'];
    sendResponse([
        'error' => 'Username atau password salah.' .
                   ($sisa > 0 ? " Sisa percobaan: $sisa." : ' Akun terkunci sementara.')
    ], 401);
}

/* ────────────────────────────────────────────────
   AMBIL NAMA DARI TABEL PROFIL
──────────────────────────────────────────────── */
$nama = getNamaByRole($conn, $user['role'], $user['id']);
$conn->close();

/* ────────────────────────────────────────────────
   BUAT SESSION AMAN
──────────────────────────────────────────────── */
$_SESSION['login_attempts'] = 0;   // reset counter

session_regenerate_id(true);        // cegah session fixation

$_SESSION['user_id']   = $user['id'];
$_SESSION['username']  = $user['username'];
$_SESSION['role']      = $user['role'];
$_SESSION['nama']      = $nama;
$_SESSION['logged_in'] = true;

sendResponse([
    'success' => true,
    'message' => 'Login berhasil. Selamat datang, ' . $nama . '!',
    'user'    => [
        'id'       => $user['id'],
        'username' => $user['username'],
        'role'     => $user['role'],
        'nama'     => $nama,
    ]
]);