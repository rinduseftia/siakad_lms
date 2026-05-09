<?php
// api/mahasiswa.php
// REST API CRUD Mahasiswa
// GET    /api/mahasiswa.php        -> ambil semua data
// GET    /api/mahasiswa.php?id=1   -> ambil satu data
// POST   /api/mahasiswa.php        -> tambah data baru
// PUT    /api/mahasiswa.php?id=1   -> update data
// DELETE /api/mahasiswa.php?id=1   -> hapus data

require_once '../config/cors.php';
require_once '../config/database.php';

$conn   = getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    // ===================== GET =====================
    case 'GET':
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM mahasiswa WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            if ($result) {
                sendResponse($result);
            } else {
                sendResponse(['error' => 'Mahasiswa tidak ditemukan'], 404);
            }
        } else {
            $result = $conn->query("SELECT * FROM mahasiswa ORDER BY id DESC");
            $data   = $result->fetch_all(MYSQLI_ASSOC);
            sendResponse($data);
        }
        break;

    // ===================== POST =====================
    case 'POST':
        $body = getRequestBody();
        if (empty($body['nim']) || empty($body['nama'])) {
            sendResponse(['error' => 'NIM dan Nama wajib diisi'], 400);
        }
        $stmt = $conn->prepare(
            "INSERT INTO mahasiswa (nim, nama, program_studi, angkatan, email, no_hp)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'sssiss',
            $body['nim'], $body['nama'], $body['program_studi'],
            $body['angkatan'], $body['email'], $body['no_hp']
        );
        if ($stmt->execute()) {
            sendResponse(['success' => true, 'id' => $conn->insert_id, 'message' => 'Mahasiswa berhasil ditambahkan'], 201);
        } else {
            sendResponse(['error' => 'Gagal menambahkan data: ' . $conn->error], 500);
        }
        break;

    // ===================== PUT =====================
    case 'PUT':
        if (!$id) sendResponse(['error' => 'ID wajib disertakan'], 400);
        $body = getRequestBody();
        if (empty($body['nim']) || empty($body['nama'])) {
            sendResponse(['error' => 'NIM dan Nama wajib diisi'], 400);
        }
        $stmt = $conn->prepare(
            "UPDATE mahasiswa SET nim=?, nama=?, program_studi=?, angkatan=?, email=?, no_hp=?
             WHERE id=?"
        );
        $stmt->bind_param(
            'sssissi',
            $body['nim'], $body['nama'], $body['program_studi'],
            $body['angkatan'], $body['email'], $body['no_hp'], $id
        );
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            sendResponse(['success' => true, 'message' => 'Mahasiswa berhasil diperbarui']);
        } else {
            sendResponse(['error' => 'Gagal update atau data tidak ditemukan'], 404);
        }
        break;

    // ===================== DELETE =====================
    case 'DELETE':
        if (!$id) sendResponse(['error' => 'ID wajib disertakan'], 400);
        $stmt = $conn->prepare("DELETE FROM mahasiswa WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            sendResponse(['success' => true, 'message' => 'Mahasiswa berhasil dihapus']);
        } else {
            sendResponse(['error' => 'Data tidak ditemukan'], 404);
        }
        break;

    default:
        sendResponse(['error' => 'Method tidak didukung'], 405);
}

$conn->close();
