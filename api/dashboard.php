<?php
// api/dashboard.php
// Ambil statistik untuk dashboard

require_once '../config/cors.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['error' => 'Method tidak didukung'], 405);
}

$conn = getConnection();

$stats = [
    'total_mahasiswa' => $conn->query("SELECT COUNT(*) as n FROM mahasiswa")->fetch_assoc()['n'],
    'total_dosen'     => $conn->query("SELECT COUNT(*) as n FROM dosen")->fetch_assoc()['n'],
    'total_matkul'    => $conn->query("SELECT COUNT(*) as n FROM mata_kuliah")->fetch_assoc()['n'],

    'mahasiswa_terbaru' => $conn->query(
        "SELECT nim, nama FROM mahasiswa ORDER BY id DESC LIMIT 5"
    )->fetch_all(MYSQLI_ASSOC),

    'dosen_terbaru' => $conn->query(
        "SELECT nidn, nama FROM dosen ORDER BY id DESC LIMIT 5"
    )->fetch_all(MYSQLI_ASSOC),

    'matkul_terbaru' => $conn->query(
        "SELECT kode, nama FROM mata_kuliah ORDER BY id DESC LIMIT 5"
    )->fetch_all(MYSQLI_ASSOC),
];

sendResponse($stats);
$conn->close();
