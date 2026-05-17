<?php
// api/dosen.php  –  SIAKAD v2
// REST API CRUD Dosen
//
//  GET    /api/dosen.php          → semua data
//  GET    /api/dosen.php?id=1     → satu data
//  POST   /api/dosen.php          → tambah (otomatis buat user login)
//  PUT    /api/dosen.php?id=1     → update
//  DELETE /api/dosen.php?id=1     → hapus satu
//  DELETE /api/dosen.php          body: {"ids":[1,2,3]}  → hapus banyak

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
       GET – ambil data dosen (JOIN users)
    ══════════════════════════════════════════ */
    case 'GET':
        if ($id) {
            $stmt = $conn->prepare(
                "SELECT d.*, u.username, u.role
                 FROM dosen d
                 LEFT JOIN users u ON d.user_id = u.id
                 WHERE d.id = ?"
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if ($result) sendResponse($result);
            else         sendResponse(['error' => 'Dosen tidak ditemukan'], 404);
        } else {
            $result = $conn->query(
                "SELECT d.*, u.username
                 FROM dosen d
                 LEFT JOIN users u ON d.user_id = u.id
                 ORDER BY d.id DESC"
            );
            sendResponse($result->fetch_all(MYSQLI_ASSOC));
        }
        break;

    /* ══════════════════════════════════════════
       POST – tambah dosen baru
       Alur: buat users → insert dosen
    ══════════════════════════════════════════ */
    case 'POST':
        if ($_SESSION['role'] !== 'admin') {
            sendResponse(['error' => 'Hanya admin yang dapat menambah data dosen'], 403);
        }

        $body = getRequestBody();

        if (empty(trim($body['nidn'] ?? '')) || empty(trim($body['nama'] ?? ''))) {
            sendResponse(['error' => 'NIDN dan Nama wajib diisi'], 400);
        }

        $nidn = trim($body['nidn']);

        // Validasi format NIDN (10 digit)
        if (!preg_match('/^\d{10}$/', $nidn)) {
            sendResponse(['error' => 'Format NIDN tidak valid (harus 10 digit angka)'], 422);
        }

        // Cek duplikat NIDN
        $chk = $conn->prepare("SELECT id FROM dosen WHERE nidn = ?");
        $chk->bind_param('s', $nidn);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            sendResponse(['error' => 'NIDN sudah terdaftar'], 409);
        }
        $chk->close();

        // Buat entri users (username=NIDN, password=NIDN)
        $userId = createUserEntry($conn, $nidn, 'dosen');
        if (!$userId) {
            sendResponse(['error' => 'NIDN sudah digunakan sebagai akun login'], 409);
        }

        $nama   = trim($body['nama']);
        $email  = filter_var(trim($body['email']  ?? ''), FILTER_SANITIZE_EMAIL);
        $no_hp  = trim($body['no_hp']  ?? '');
        $status = in_array($body['status'] ?? '', ['Aktif','Tidak Aktif'])
                  ? $body['status'] : 'Aktif';

        $stmt = $conn->prepare(
            "INSERT INTO dosen (user_id, nidn, nama, email, no_hp, status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('isssss', $userId, $nidn, $nama, $email, $no_hp, $status);

        if ($stmt->execute()) {
            sendResponse([
                'success' => true,
                'id'      => $conn->insert_id,
                'message' => 'Dosen berhasil ditambahkan. Username & password = NIDN.'
            ], 201);
        } else {
            deleteUserEntry($conn, $userId); // rollback
            sendResponse(['error' => 'Gagal menyimpan data: ' . $conn->error], 500);
        }
        break;

    /* ══════════════════════════════════════════
       PUT – update data dosen
    ══════════════════════════════════════════ */
    case 'PUT':
        if (!$id) sendResponse(['error' => 'ID wajib disertakan'], 400);

        if ($_SESSION['role'] !== 'admin') {
            sendResponse(['error' => 'Hanya admin yang dapat mengubah data dosen'], 403);
        }

        $body = getRequestBody();

        if (empty(trim($body['nidn'] ?? '')) || empty(trim($body['nama'] ?? ''))) {
            sendResponse(['error' => 'NIDN dan Nama wajib diisi'], 400);
        }

        $nidn   = trim($body['nidn']);
        $nama   = trim($body['nama']);
        $email  = filter_var(trim($body['email']  ?? ''), FILTER_SANITIZE_EMAIL);
        $no_hp  = trim($body['no_hp'] ?? '');
        $status = in_array($body['status'] ?? '', ['Aktif','Tidak Aktif'])
                  ? $body['status'] : 'Aktif';

        // Cek NIDN tidak dipakai dosen lain
        $chk = $conn->prepare("SELECT id FROM dosen WHERE nidn = ? AND id != ?");
        $chk->bind_param('si', $nidn, $id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            sendResponse(['error' => 'NIDN sudah digunakan oleh dosen lain'], 409);
        }
        $chk->close();

        $stmt = $conn->prepare(
            "UPDATE dosen SET nidn=?, nama=?, email=?, no_hp=?, status=? WHERE id=?"
        );
        $stmt->bind_param('sssssi', $nidn, $nama, $email, $no_hp, $status, $id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            sendResponse(['success' => true, 'message' => 'Data dosen berhasil diperbarui']);
        } else {
            sendResponse(['error' => 'Gagal update atau data tidak ditemukan'], 404);
        }
        break;

    /* ══════════════════════════════════════════
       DELETE – hapus satu atau banyak sekaligus
       Single : DELETE ?id=1
       Bulk   : DELETE body = {"ids":[1,2,3]}
    ══════════════════════════════════════════ */
    case 'DELETE':
        if ($_SESSION['role'] !== 'admin') {
            sendResponse(['error' => 'Hanya admin yang dapat menghapus data dosen'], 403);
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

            // Ambil user_id dulu
            $stmt = $conn->prepare(
                "SELECT user_id FROM dosen WHERE id IN ($placeholders)"
            );
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $rows    = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $userIds = array_filter(array_column($rows, 'user_id'));
            $stmt->close();

            // Hapus dosen
            $stmt = $conn->prepare(
                "DELETE FROM dosen WHERE id IN ($placeholders)"
            );
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            // Hapus user terkait
            foreach ($userIds as $uid) {
                deleteUserEntry($conn, (int) $uid);
            }

            sendResponse([
                'success' => true,
                'message' => "$deleted dosen berhasil dihapus"
            ]);
        }

        /* ─── SINGLE DELETE ─── */
        elseif ($id) {
            $stmt = $conn->prepare("SELECT user_id FROM dosen WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM dosen WHERE id = ?");
            $stmt->bind_param('i', $id);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                if (!empty($row['user_id'])) {
                    deleteUserEntry($conn, (int) $row['user_id']);
                }
                sendResponse(['success' => true, 'message' => 'Dosen berhasil dihapus']);
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