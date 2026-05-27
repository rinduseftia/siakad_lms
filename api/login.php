<?php
// api/login.php  –  SIAKAD v2
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
        'success' => false, 
        'message' => 'Terlalu banyak percobaan login. Tunggu 15 menit.'
    ], 429);
}

/* ────────────────────────────────────────────────
   VALIDASI METHOD & INPUT
──────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['success' => false, 'message' => 'Method tidak didukung'], 405);
}

// Membaca request body
$body     = getRequestBody(); 
$username = trim($body['nim'] ?? $body['username'] ?? ''); 
$password = $body['password'] ?? '';       

if ($username === '' || $password === '') {
    sendResponse(['success' => false, 'message' => 'NIM/Username dan password wajib diisi'], 400);
}

$username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

/* ────────────────────────────────────────────────
   CEK USER DI DATABASE (KODE SUDAH DI-FIX / BERSIH)
──────────────────────────────────────────────── */
$conn = getConnection();

$stmt = $conn->prepare(
    "SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1"
);
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Cek password teks biasa ATAU hash bcrypt
$isPasswordMatch = false;
if ($user) {
    if ($password === $user['password'] || password_verify($password, $user['password'])) {
        $isPasswordMatch = true;
    }
}

// Gagal login
if (!$user || !$isPasswordMatch) {
    $_SESSION['login_attempts']++;
    $sisa = 5 - $_SESSION['login_attempts'];
    
    // Pastikan koneksi ditutup meskipun gagal login
    $conn->close();
    
    sendResponse([
        'success' => false,
        'message' => 'Username atau password salah. ' .
                     ($sisa > 0 ? "Sisa percobaan: $sisa." : 'Akun terkunci sementara.')
    ], 401);
}

/* ────────────────────────────────────────────────
   AMBIL NAMA DARI TABEL PROFIL (KODE ASLIMU)
──────────────────────────────────────────────── */
$nama = getNamaByRole($conn, $user['role'], $user['id']);

/* ────────────────────────────────────────────────
   TAMBAHAN: AMBIL INFO EKSTRA KHUSUS MAHASISWA
──────────────────────────────────────────────── */
$nim   = $user['username']; // Default: nim adalah username
$prodi = '';
$jur   = '';

if ($user['role'] === 'mahasiswa') {
    $stmt_mhs = $conn->prepare("SELECT nim, prodi, jur FROM mahasiswa WHERE user_id = ? LIMIT 1");
    if ($stmt_mhs) {
        $stmt_mhs->bind_param('i', $user['id']);
        $stmt_mhs->execute();
        $res_mhs = $stmt_mhs->get_result()->fetch_assoc();
        $stmt_mhs->close();
        
        if ($res_mhs) {
            $nim   = $res_mhs['nim'] ?? $nim;
            $prodi = $res_mhs['prodi'] ?? '';
            $jur   = $res_mhs['jur'] ?? '';
        }
    }
}

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
$_SESSION['nim']       = $nim;     // Simpan ke session juga
$_SESSION['logged_in'] = true;

// Mengirimkan response sukses yang langsung dibaca oleh Javascript
sendResponse([
    'success' => true,
    'message' => 'Login berhasil. Selamat datang, ' . $nama . '!',
    'user'    => [
        'id'       => $user['id'],
        'username' => $user['username'],
        'role'     => $user['role'],
        'nama'     => $nama,
        // Data Ekstra untuk Mahasiswa:
        'nim'      => $nim,
        'prodi'    => $prodi,
        'jur'      => $jur
    ]
]);