<?php
// api/mahasiswa.php  –  SIAKAD v2
// REST API CRUD Mahasiswa
//
//  GET    /api/mahasiswa.php           → semua data
//  GET    /api/mahasiswa.php?nim=xxx   → satu data
//  POST   /api/mahasiswa.php           → tambah (otomatis buat user login)
//  PUT    /api/mahasiswa.php?nim=xxx   → update
//  DELETE /api/mahasiswa.php?nim=xxx   → hapus satu
//  DELETE /api/mahasiswa.php           body: {"nims":["xxx","yyy"]}  → hapus banyak

require_once '../config/cors.php';
require_once '../config/database.php';

/* ── Guard: harus sudah login ── */
session_start();
if (empty($_SESSION['logged_in'])) {
    sendResponse(['error' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
}

$conn   = getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$nim    = isset($_GET['nim']) ? trim($_GET['nim']) : null;

/* ────────────────────────────────────────────
   Helper: validasi format NIM (10 digit angka)
──────────────────────────────────────────── */
function validNim(string $nim): bool {
    return (bool) preg_match('/^\d{10}$/', $nim);
}

switch ($method) {

    /* ══════════════════════════════════════════
       GET – ambil data mahasiswa
       JOIN users agar username ikut tampil
    ══════════════════════════════════════════ */
    case 'GET':
        if ($nim) {
            $stmt = $conn->prepare(
                "SELECT m.*, u.username, u.role
                 FROM mahasiswa m
                 LEFT JOIN users u ON m.user_id = u.id
                 WHERE m.nim = ?"
            );
            $stmt->bind_param('s', $nim);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if ($result) sendResponse($result);
            else         sendResponse(['error' => 'Mahasiswa tidak ditemukan'], 404);
        } else {
            $result = $conn->query(
                "SELECT m.*, u.username
                 FROM mahasiswa m
                 LEFT JOIN users u ON m.user_id = u.id
                 ORDER BY m.created_at DESC"
            );
            sendResponse($result->fetch_all(MYSQLI_ASSOC));
        }
        break;

    /* ══════════════════════════════════════════
       POST – tambah mahasiswa baru
       Alur: buat users → insert mahasiswa
    ══════════════════════════════════════════ */
    case 'POST':
        // Hanya admin yang boleh tambah
        if ($_SESSION['role'] !== 'admin') {
            sendResponse(['error' => 'Hanya admin yang dapat menambah data mahasiswa'], 403);
        }

        $body = getRequestBody();

        // Validasi wajib
        foreach (['nim', 'nama'] as $f) {
            if (empty(trim($body[$f] ?? ''))) {
                sendResponse(['error' => ucfirst($f) . ' wajib diisi'], 400);
            }
        }

        $nim_input = trim($body['nim']);

        if (!validNim($nim_input)) {
            sendResponse(['error' => 'Format NIM tidak valid (harus 10 digit angka)'], 422);
        }

        // Cek duplikat NIM
        $chk = $conn->prepare("SELECT nim FROM mahasiswa WHERE nim = ?");
        $chk->bind_param('s', $nim_input);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            sendResponse(['error' => 'NIM sudah terdaftar'], 409);
        }
        $chk->close();

        // Buat entri users (username=NIM, password=NIM)
        $userId = createUserEntry($conn, $nim_input, 'mahasiswa');
        if (!$userId) {
            sendResponse(['error' => 'NIM sudah digunakan sebagai akun login'], 409);
        }

        // Siapkan field
        $nama      = trim($body['nama']);
        $jur       = trim($body['jur']           ?? '');
        $prodi     = trim($body['program_studi'] ?? '');
        $email     = filter_var(trim($body['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $agama     = trim($body['agama']         ?? '');
        $status    = trim($body['status']        ?? 'Aktif');
        $jk        = trim($body['jk']            ?? '');
        $tmp_lahir = trim($body['tmp_lahir']     ?? '');
        $tgl_lahir = !empty($body['tgl_lahir']) ? $body['tgl_lahir'] : null;
        $alamat    = trim($body['alamat']        ?? '');

        $stmt = $conn->prepare(
            "INSERT INTO mahasiswa
               (nim, user_id, nama, jur, program_studi, email, agama, status, jk, tmp_lahir, tgl_lahir, alamat)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'sissssssssss',
            $nim_input, $userId, $nama, $jur, $prodi, $email,
            $agama, $status, $jk, $tmp_lahir, $tgl_lahir, $alamat
        );

        if ($stmt->execute()) {
            sendResponse([
                'success' => true,
                'nim'     => $nim_input,
                'message' => 'Mahasiswa berhasil ditambahkan. Username & password = NIM.'
            ], 201);
        } else {
            deleteUserEntry($conn, $userId); // rollback user
            sendResponse(['error' => 'Gagal menyimpan data: ' . $conn->error], 500);
        }
        break;

    /* ══════════════════════════════════════════
       PUT – update data mahasiswa
    ══════════════════════════════════════════ */
    case 'PUT':
        if (!$nim) sendResponse(['error' => 'NIM wajib disertakan di query string'], 400);

        if ($_SESSION['role'] !== 'admin') {
            sendResponse(['error' => 'Hanya admin yang dapat mengubah data mahasiswa'], 403);
        }

        $body = getRequestBody();

        if (empty(trim($body['nama'] ?? ''))) {
            sendResponse(['error' => 'Nama wajib diisi'], 400);
        }

        $nama      = trim($body['nama']);
        $jur       = trim($body['jur']           ?? '');
        $prodi     = trim($body['program_studi'] ?? '');
        $email     = filter_var(trim($body['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $agama     = trim($body['agama']         ?? '');
        $status    = trim($body['status']        ?? 'Aktif');
        $jk        = trim($body['jk']            ?? '');
        $tmp_lahir = trim($body['tmp_lahir']     ?? '');
        $tgl_lahir = !empty($body['tgl_lahir']) ? $body['tgl_lahir'] : null;
        $alamat    = trim($body['alamat']        ?? '');

        $stmt = $conn->prepare(
            "UPDATE mahasiswa
             SET nama=?, jur=?, program_studi=?, email=?, agama=?, status=?,
                 jk=?, tmp_lahir=?, tgl_lahir=?, alamat=?
             WHERE nim=?"
        );
        $stmt->bind_param(
            'sssssssssss',
            $nama, $jur, $prodi, $email, $agama, $status,
            $jk, $tmp_lahir, $tgl_lahir, $alamat, $nim
        );

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            sendResponse(['success' => true, 'message' => 'Data mahasiswa berhasil diperbarui']);
        } else {
            sendResponse(['error' => 'Gagal update atau data tidak ditemukan'], 404);
        }
        break;

    case 'DELETE':
        if ($_SESSION['role'] !== 'admin') {
            sendResponse(['error' => 'Hanya admin yang dapat menghapus data mahasiswa'], 403);
        }

        $body = getRequestBody();

        /* ─── BULK DELETE ─── */
        if (!empty($body['nims']) && is_array($body['nims'])) {
            $nims = array_values(array_filter(array_map('trim', $body['nims'])));

            if (empty($nims)) {
                sendResponse(['error' => 'Daftar NIM kosong'], 400);
            }
            if (count($nims) > 100) {
                sendResponse(['error' => 'Maksimal 100 data sekaligus'], 400);
            }

            $placeholders = implode(',', array_fill(0, count($nims), '?'));
            $types        = str_repeat('s', count($nims));

            // Ambil user_id sebelum hapus
            $stmt = $conn->prepare(
                "SELECT user_id FROM mahasiswa WHERE nim IN ($placeholders)"
            );
            $stmt->bind_param($types, ...$nims);
            $stmt->execute();
            $rows    = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $userIds = array_filter(array_column($rows, 'user_id'));
            $stmt->close();

            // Hapus mahasiswa
            $stmt = $conn->prepare(
                "DELETE FROM mahasiswa WHERE nim IN ($placeholders)"
            );
            $stmt->bind_param($types, ...$nims);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            // Hapus entri users terkait
            foreach ($userIds as $uid) {
                deleteUserEntry($conn, (int) $uid);
            }

            sendResponse([
                'success' => true,
                'message' => "$deleted mahasiswa berhasil dihapus"
            ]);
        }

        /* ─── SINGLE DELETE ─── */
        elseif ($nim) {
            // Ambil user_id dulu
            $stmt = $conn->prepare("SELECT user_id FROM mahasiswa WHERE nim = ?");
            $stmt->bind_param('s', $nim);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM mahasiswa WHERE nim = ?");
            $stmt->bind_param('s', $nim);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                if (!empty($row['user_id'])) {
                    deleteUserEntry($conn, (int) $row['user_id']);
                }
                sendResponse(['success' => true, 'message' => 'Mahasiswa berhasil dihapus']);
            } else {
                sendResponse(['error' => 'Data tidak ditemukan'], 404);
            }
        } else {
            sendResponse(['error' => 'Sertakan ?nim=xxx atau body {nims:[...]}'], 400);
        }
        break;

    default:
        sendResponse(['error' => 'Method tidak didukung'], 405);
}

$conn->close();