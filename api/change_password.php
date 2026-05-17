<?php
// api/change_password.php  –  SIAKAD v2
// Ganti password user yang sedang login (semua role)
// Update dilakukan di tabel `users` (central auth)

require_once '../config/cors.php';
require_once '../config/database.php';

/* ── Guard: harus sudah login ── */
session_start();
if (empty($_SESSION['logged_in'])) {
    sendResponse(['error' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['error' => 'Method tidak didukung'], 405);
}

/* ── Input ── */
$body        = getRequestBody();  // gunakan helper yang sudah ada di cors.php
$old_password = $body['old_password'] ?? '';
$new_password = $body['new_password'] ?? '';
$confirm      = $body['confirm_password'] ?? '';

/* ── Validasi input ── */
if (empty($old_password) || empty($new_password)) {
    sendResponse(['error' => 'Password lama dan baru wajib diisi'], 400);
}

if (strlen($new_password) < 6) {
    sendResponse(['error' => 'Password baru minimal 6 karakter'], 422);
}

if ($new_password !== $confirm) {
    sendResponse(['error' => 'Konfirmasi password tidak cocok'], 422);
}

if ($old_password === $new_password) {
    sendResponse(['error' => 'Password baru tidak boleh sama dengan password lama'], 422);
}

/* ── Ambil data user dari tabel users ── */
$conn    = getConnection();
$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT id, username, password, role FROM users WHERE id = ?"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    sendResponse(['error' => 'User tidak ditemukan. Silakan login ulang.'], 404);
}

/* ── Cek password lama ── */
if ($old_password !== $user['password']) {
    sendResponse(['error' => 'Password lama salah'], 400);
}

/* ── Update password di tabel users ── */
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param('si', $new_password, $user_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    // Perbarui session jika ada (tidak wajib karena password bukan di session)
    sendResponse([
        'success' => true,
        'message' => 'Password berhasil diubah. Silakan login ulang dengan password baru.'
    ]);
} else {
    $stmt->close();
    $conn->close();
    sendResponse(['error' => 'Gagal mengubah password. Coba lagi.'], 500);
}