<?php

require_once '../config/cors.php';
require_once '../config/database.php';

$conn = getConnection();

$data = json_decode(file_get_contents("php://input"), true);

$old = $data['old_password'] ?? '';
$new = $data['new_password'] ?? '';

if (!$old || !$new) {
    sendResponse([
        'error' => 'Data tidak lengkap'
    ], 400);
}

$query = $conn->query(
    "SELECT * FROM admin WHERE username='admin'"
);

$admin = $query->fetch_assoc();

if (!$admin) {
    sendResponse([
        'error' => 'Admin tidak ditemukan'
    ], 404);
}

if ($admin['password'] !== $old) {
    sendResponse([
        'error' => 'Password lama salah'
    ], 400);
}

$stmt = $conn->prepare(
    "UPDATE admin SET password=? WHERE username='admin'"
);

$stmt->bind_param('s', $new);

if ($stmt->execute()) {

    sendResponse([
        'success' => true,
        'message' => 'Password berhasil diubah'
    ]);

} else {

    sendResponse([
        'error' => 'Gagal mengubah password'
    ], 500);

}

$conn->close();