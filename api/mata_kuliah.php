<?php
// api/mata_kuliah.php
// REST API CRUD Mata Kuliah

require_once '../config/cors.php';
require_once '../config/database.php';

$conn   = getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    case 'GET':
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM mata_kuliah WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            if ($result) sendResponse($result);
            else sendResponse(['error' => 'Mata kuliah tidak ditemukan'], 404);
        } else {
            $result = $conn->query("SELECT * FROM mata_kuliah ORDER BY id DESC");
            sendResponse($result->fetch_all(MYSQLI_ASSOC));
        }
        break;

    case 'POST':
        $body = getRequestBody();
        if (empty($body['kode']) || empty($body['nama'])) {
            sendResponse(['error' => 'Kode dan Nama wajib diisi'], 400);
        }
        $sks      = $body['sks']      ?? 3;
        $semester = $body['semester'] ?? 1;
        $stmt = $conn->prepare("INSERT INTO mata_kuliah (kode, nama, sks, semester) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssii', $body['kode'], $body['nama'], $sks, $semester);
        if ($stmt->execute()) {
            sendResponse(['success' => true, 'id' => $conn->insert_id, 'message' => 'Mata kuliah berhasil ditambahkan'], 201);
        } else {
            sendResponse(['error' => 'Gagal: ' . $conn->error], 500);
        }
        break;

    case 'PUT':
        if (!$id) sendResponse(['error' => 'ID wajib disertakan'], 400);
        $body     = getRequestBody();
        $sks      = $body['sks']      ?? 3;
        $semester = $body['semester'] ?? 1;
        $stmt = $conn->prepare("UPDATE mata_kuliah SET kode=?, nama=?, sks=?, semester=? WHERE id=?");
        $stmt->bind_param('ssiii', $body['kode'], $body['nama'], $sks, $semester, $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            sendResponse(['success' => true, 'message' => 'Mata kuliah berhasil diperbarui']);
        } else {
            sendResponse(['error' => 'Gagal update atau data tidak ditemukan'], 404);
        }
        break;

    case 'DELETE':
        if (!$id) sendResponse(['error' => 'ID wajib disertakan'], 400);
        $stmt = $conn->prepare("DELETE FROM mata_kuliah WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            sendResponse(['success' => true, 'message' => 'Mata kuliah berhasil dihapus']);
        } else {
            sendResponse(['error' => 'Data tidak ditemukan'], 404);
        }
        break;

    default:
        sendResponse(['error' => 'Method tidak didukung'], 405);
}

$conn->close();
