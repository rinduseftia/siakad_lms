<?php
// api/dashboard.php  –  SIAKAD v2
// Statistik dashboard + info user yang sedang login

require_once '../config/cors.php';
require_once '../config/database.php';

/* ── Guard: harus sudah login ── */
session_start();
if (empty($_SESSION['logged_in'])) {
    sendResponse(['error' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['error' => 'Method tidak didukung'], 405);
}

$conn = getConnection();
$role = $_SESSION['role'];

/* ────────────────────────────────────────────
   Statistik umum (sama untuk semua role)
──────────────────────────────────────────── */
$totalMahasiswa = (int) $conn->query("SELECT COUNT(*) AS n FROM mahasiswa")->fetch_assoc()['n'];
$totalDosen     = (int) $conn->query("SELECT COUNT(*) AS n FROM dosen")->fetch_assoc()['n'];
$totalMatkul    = (int) $conn->query("SELECT COUNT(*) AS n FROM mata_kuliah")->fetch_assoc()['n'];

/* ────────────────────────────────────────────
   Data terbaru
   CATATAN: mahasiswa.nim adalah PK (VARCHAR),
   bukan id → ORDER BY created_at
──────────────────────────────────────────── */
$mahasiswaTerbaru = $conn->query(
    "SELECT nim, nama, program_studi FROM mahasiswa ORDER BY created_at DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$dosenTerbaru = $conn->query(
    "SELECT nidn, nama, status FROM dosen ORDER BY id DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$matkulTerbaru = $conn->query(
    "SELECT kode, nama, sks, semester FROM mata_kuliah ORDER BY id DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

/* ────────────────────────────────────────────
   Info profil user yang sedang login
──────────────────────────────────────────── */
$profilUser = null;

if ($role === 'mahasiswa') {
    $stmt = $conn->prepare(
        "SELECT nim, nama, program_studi, email, jur, status
         FROM mahasiswa WHERE user_id = ?"
    );
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $profilUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();

} elseif ($role === 'dosen') {
    $stmt = $conn->prepare(
        "SELECT nidn, nama, email, no_hp, status
         FROM dosen WHERE user_id = ?"
    );
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $profilUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();

} elseif ($role === 'admin') {
    $stmt = $conn->prepare(
        "SELECT nama, username FROM admin WHERE user_id = ?"
    );
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $profilUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/* ────────────────────────────────────────────
   Respons
──────────────────────────────────────────── */
sendResponse([
    // Siapa yang login
    'user_aktif' => [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nama'     => $_SESSION['nama'],
        'role'     => $_SESSION['role'],
    ],
    'profil' => $profilUser,

    // Statistik
    'statistik' => [
        'total_mahasiswa' => $totalMahasiswa,
        'total_dosen'     => $totalDosen,
        'total_matkul'    => $totalMatkul,
    ],

    // Data terbaru (hanya tampil penuh untuk admin)
    'mahasiswa_terbaru' => $role === 'admin' ? $mahasiswaTerbaru : [],
    'dosen_terbaru'     => $role === 'admin' ? $dosenTerbaru     : [],
    'matkul_terbaru'    => $matkulTerbaru,   // semua role boleh lihat
]);

$conn->close();