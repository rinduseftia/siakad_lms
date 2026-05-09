<?php
// api/dosen.php
// REST API CRUD Dosen

require_once '../config/cors.php';
require_once '../config/database.php';

$conn   = getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    case 'GET':
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM dosen WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            if ($result) sendResponse($result);
            else sendResponse(['error' => 'Dosen tidak ditemukan'], 404);
        } else {
            $result = $conn->query("SELECT * FROM dosen ORDER BY id DESC");
            sendResponse($result->fetch_all(MYSQLI_ASSOC));
        }
        break;

    case 'POST':
        $body = getRequestBody();
        if (empty($body['nidn']) || empty($body['nama'])) {
            sendResponse(['error' => 'NIDN dan Nama wajib diisi'], 400);
        }
        $status = $body['status'] ?? 'Aktif';
        $stmt = $conn->prepare(
            "INSERT INTO dosen (nidn, nama, email, no_hp, status) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssss', $body['nidn'], $body['nama'], $body['email'], $body['no_hp'], $status);
        if ($stmt->execute()) {
            sendResponse(['success' => true, 'id' => $conn->insert_id, 'message' => 'Dosen berhasil ditambahkan'], 201);
        } else {
            sendResponse(['error' => 'Gagal: ' . $conn->error], 500);
        }
        break;

    case 'PUT':
        if (!$id) sendResponse(['error' => 'ID wajib disertakan'], 400);
        $body   = getRequestBody();
        $status = $body['status'] ?? 'Aktif';
        $stmt   = $conn->prepare(
            "UPDATE dosen SET nidn=?, nama=?, email=?, no_hp=?, status=? WHERE id=?"
        );
        $stmt->bind_param('sssssi', $body['nidn'], $body['nama'], $body['email'], $body['no_hp'], $status, $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            sendResponse(['success' => true, 'message' => 'Dosen berhasil diperbarui']);
        } else {
            sendResponse(['error' => 'Gagal update atau data tidak ditemukan'], 404);
        }
        break;

    case 'DELETE':
        if (!$id) sendResponse(['error' => 'ID wajib disertakan'], 400);
        $stmt = $conn->prepare("DELETE FROM dosen WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            sendResponse(['success' => true, 'message' => 'Dosen berhasil dihapus']);
        } else {
            sendResponse(['error' => 'Data tidak ditemukan'], 404);
        }
        break;

    default:
        sendResponse(['error' => 'Method tidak didukung'], 405);
}

$conn->close();
