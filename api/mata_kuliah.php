<?php
// api/mata_kuliah.php  –  SIAKAD v2
// REST API CRUD Mata Kuliah
//
//  GET    /api/mata_kuliah.php        → semua data
//  GET    /api/mata_kuliah.php?id=1   → satu data
//  POST   /api/mata_kuliah.php        → tambah
//  PUT    /api/mata_kuliah.php?id=1   → update
//  DELETE /api/mata_kuliah.php?id=1   → hapus satu
//  DELETE /api/mata_kuliah.php        body: {"ids":[1,2,3]}  → hapus banyak

require_once '../config/cors.php';
require_once '../config/database.php';

/* ── Guard: harus sudah login ── */
session_start();
if (empty($_SESSION['logged_in'])) {
    sendResponse(['error' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
}

$conn   = getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($method) {

    /* ══════════════════════════════════════════
       GET
    ══════════════════════════════════════════ */
    case 'GET':
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM mata_kuliah WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            if ($result) sendResponse($result);
            else         sendResponse(['error' => 'Mata kuliah tidak ditemukan'], 404);
        } else {
            $result = $conn->query("SELECT * FROM mata_kuliah ORDER BY semester ASC, kode ASC");
            sendResponse($result->fetch_all(MYSQLI_ASSOC));
        }
        break;

    /* ══════════════════════════════════════════
       POST – tambah mata kuliah baru
    ══════════════════════════════════════════ */
    case 'POST':
        if ($_SESSION['role'] !== 'admin') {
            sendResponse(['error' => 'Hanya admin yang dapat menambah mata kuliah'], 403);
        }

        $body = getRequestBody();

        if (empty(trim($body['kode'] ?? '')) || empty(trim($body['nama'] ?? ''))) {
            sendResponse(['error' => 'Kode dan Nama wajib diisi'], 400);
        }

        $kode     = strtoupper(trim($body['kode']));
        $nama     = trim($body['nama']);
        $sks      = (int) ($body['sks']      ?? 3);
        $semester = (int) ($body['semester'] ?? 1);

        // Validasi range
        if ($sks < 1 || $sks > 6) {
            sendResponse(['error' => 'SKS harus antara 1–6'], 422);
        }
        if ($semester < 1 || $semester > 8) {
            sendResponse(['error' => 'Semester harus antara 1–8'], 422);
        }

        // Cek duplikat kode
        $chk = $conn->prepare("SELECT id FROM mata_kuliah WHERE kode = ?");
        $chk->bind_param('s', $kode);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            sendResponse(['error' => 'Kode mata kuliah sudah digunakan'], 409);
        }
        $chk->close();

        $stmt = $conn->prepare(
            "INSERT INTO mata_kuliah (kode, nama, sks, semester) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('ssii', $kode, $nama, $sks, $semester);

        if ($stmt->execute()) {
            sendResponse([
                'success' => true,
                'id'      => $conn->insert_id,
                'message' => 'Mata kuliah berhasil ditambahkan'
            ], 201);
        } else {
            sendResponse(['error' => 'Gagal menyimpan data: ' . $conn->error], 500);
        }
        break;

    /* ══════════════════════════════════════════
       PUT – update mata kuliah
    ══════════════════════════════════════════ */
    case 'PUT':
        if (!$id) sendResponse(['error' => 'ID wajib disertakan'], 400);

        if ($_SESSION['role'] !== 'admin') {
            sendResponse(['error' => 'Hanya admin yang dapat mengubah mata kuliah'], 403);
        }

        $body = getRequestBody();

        if (empty(trim($body['kode'] ?? '')) || empty(trim($body['nama'] ?? ''))) {
            sendResponse(['error' => 'Kode dan Nama wajib diisi'], 400);
        }

        $kode     = strtoupper(trim($body['kode']));
        $nama     = trim($body['nama']);
        $sks      = (int) ($body['sks']      ?? 3);
        $semester = (int) ($body['semester'] ?? 1);

        if ($sks < 1 || $sks > 6)      sendResponse(['error' => 'SKS harus antara 1–6'], 422);
        if ($semester < 1 || $semester > 8) sendResponse(['error' => 'Semester harus antara 1–8'], 422);

        // Cek kode tidak dipakai mata kuliah lain
        $chk = $conn->prepare("SELECT id FROM mata_kuliah WHERE kode = ? AND id != ?");
        $chk->bind_param('si', $kode, $id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            sendResponse(['error' => 'Kode sudah digunakan mata kuliah lain'], 409);
        }
        $chk->close();

        $stmt = $conn->prepare(
            "UPDATE mata_kuliah SET kode=?, nama=?, sks=?, semester=? WHERE id=?"
        );
        $stmt->bind_param('ssiii', $kode, $nama, $sks, $semester, $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(['success' => true, 'message' => 'Mata kuliah berhasil diperbarui']);
            } else {
                sendResponse(['success' => true, 'message' => 'Tidak ada perubahan data']);
            }
        } else {
            sendResponse(['error' => 'Gagal update: ' . $conn->error], 500);
        }
        break;

    /* ══════════════════════════════════════════
       DELETE – hapus satu atau banyak sekaligus
       Single : DELETE ?id=1
       Bulk   : DELETE body = {"ids":[1,2,3]}
    ══════════════════════════════════════════ */
    case 'DELETE':
        if ($_SESSION['role'] !== 'admin') {
            sendResponse(['error' => 'Hanya admin yang dapat menghapus mata kuliah'], 403);
        }

        $body = getRequestBody();

        /* ─── BULK DELETE ─── */
        if (!empty($body['ids']) && is_array($body['ids'])) {
            $ids = array_values(array_filter(array_map('intval', $body['ids'])));

            if (empty($ids)) {
                sendResponse(['error' => 'Daftar ID kosong'], 400);
            }
            if (count($ids) > 100) {
                sendResponse(['error' => 'Maksimal 100 data sekaligus'], 400);
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types        = str_repeat('i', count($ids));

            $stmt = $conn->prepare(
                "DELETE FROM mata_kuliah WHERE id IN ($placeholders)"
            );
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            sendResponse([
                'success' => true,
                'message' => "$deleted mata kuliah berhasil dihapus"
            ]);
        }

        /* ─── SINGLE DELETE ─── */
        elseif ($id) {
            $stmt = $conn->prepare("DELETE FROM mata_kuliah WHERE id = ?");
            $stmt->bind_param('i', $id);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                sendResponse(['success' => true, 'message' => 'Mata kuliah berhasil dihapus']);
            } else {
                sendResponse(['error' => 'Data tidak ditemukan'], 404);
            }
        } else {
            sendResponse(['error' => 'Sertakan ?id=1 atau body {ids:[...]}'], 400);
        }
        break;

    default:
        sendResponse(['error' => 'Method tidak didukung'], 405);
}

$conn->close();