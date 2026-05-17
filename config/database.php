<?php
// config/database.php
// Konfigurasi koneksi MySQL – SIAKAD v2

define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // default XAMPP
define('DB_PASS', '');       // default XAMPP (kosong)
define('DB_NAME', 'siakad');

/* ──────────────────────────────────────────────
   Koneksi dasar
────────────────────────────────────────────── */
function getConnection(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(['error' => 'Koneksi database gagal: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

/* ──────────────────────────────────────────────
   AUTH: Login
   Cek tabel `users`, lalu ambil nama dari
   tabel profil sesuai role.
   Return: array info user | null jika gagal
────────────────────────────────────────────── */
function loginUser(string $username, string $password): ?array {
    $conn = getConnection();

    $stmt = $conn->prepare(
        "SELECT id, username, password, role FROM users WHERE username = ?"
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return null;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Cek password (plain-text, bisa di-upgrade ke password_verify)
    if ($password !== $user['password']) {
        $conn->close();
        return null;
    }

    // Ambil nama dari tabel profil sesuai role
    $nama = getNamaByRole($conn, $user['role'], $user['id']);
    $conn->close();

    return [
        'id'       => $user['id'],
        'username' => $user['username'],
        'role'     => $user['role'],
        'nama'     => $nama,
    ];
}

/* ──────────────────────────────────────────────
   Ambil nama dari tabel profil berdasar role
────────────────────────────────────────────── */
function getNamaByRole(mysqli $conn, string $role, int $userId): string {
    switch ($role) {
        case 'admin':
            $stmt = $conn->prepare(
                "SELECT nama FROM admin WHERE user_id = ?"
            );
            break;
        case 'mahasiswa':
            $stmt = $conn->prepare(
                "SELECT nama FROM mahasiswa WHERE user_id = ?"
            );
            break;
        case 'dosen':
            $stmt = $conn->prepare(
                "SELECT nama FROM dosen WHERE user_id = ?"
            );
            break;
        default:
            return 'Unknown';
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row['nama'] ?? $role;
}

/* ──────────────────────────────────────────────
   Ambil data mahasiswa lengkap (JOIN users)
   Berguna untuk halaman profil mahasiswa
────────────────────────────────────────────── */
function getMahasiswaByUserId(int $userId): ?array {
    $conn = getConnection();
    $stmt = $conn->prepare(
        "SELECT m.*, u.username, u.role
         FROM mahasiswa m
         JOIN users u ON m.user_id = u.id
         WHERE m.user_id = ?"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row ?: null;
}

/* ──────────────────────────────────────────────
   Ambil data dosen lengkap (JOIN users)
────────────────────────────────────────────── */
function getDosenByUserId(int $userId): ?array {
    $conn = getConnection();
    $stmt = $conn->prepare(
        "SELECT d.*, u.username, u.role
         FROM dosen d
         JOIN users u ON d.user_id = u.id
         WHERE d.user_id = ?"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row ?: null;
}

/* ──────────────────────────────────────────────
   Saat INSERT mahasiswa/dosen baru:
   buat entri users dulu, return user_id
────────────────────────────────────────────── */
function createUserEntry(mysqli $conn, string $username, string $role): ?int {
    // Default password = username (NIM untuk mahasiswa, NIDN untuk dosen)
    $password = $username;

    $stmt = $conn->prepare(
        "INSERT INTO users (username, password, role) VALUES (?, ?, ?)"
    );
    $stmt->bind_param('sss', $username, $password, $role);

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $newId = $conn->insert_id;
    $stmt->close();
    return $newId;
}

/* ──────────────────────────────────────────────
   Saat DELETE mahasiswa/dosen:
   hapus juga entri di tabel users
────────────────────────────────────────────── */
function deleteUserEntry(mysqli $conn, int $userId): void {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}