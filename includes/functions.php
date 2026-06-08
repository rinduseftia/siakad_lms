<?php
/**
 * Helper functions — nilai, IPK, pagination, dll.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function status_badge(string $status): string
{
    $raw = trim($status);
    $s = strtolower($raw);
    $class = 'badge-info';

    if (in_array($s, ['aktif', 'hadir', 'buka'], true)) {
        $class = 'badge-aktif';
    } elseif (in_array($s, ['nonaktif', 'alpha', 'tutup'], true) || str_starts_with($s, 'ditolak')) {
        $class = 'badge-nonaktif';
    } elseif ($s === 'pending' || in_array($s, ['izin', 'sakit'], true)) {
        $class = 'badge-warn';
    } elseif (str_starts_with($s, 'disetujui')) {
        $class = 'badge-aktif';
    } elseif (in_array($s, ['admin', 'dosen', 'mahasiswa'], true)) {
        $class = 'badge-role';
    } elseif (in_array($s, ['a', 'b', 'c'], true)) {
        $class = 'badge-aktif';
    } elseif (in_array($s, ['d', 'e'], true)) {
        $class = 'badge-nonaktif';
    }

    return '<span class="badge-status ' . $class . '">' . e($raw) . '</span>';
}

function hitung_nilai_akhir(float $tugas, float $quiz, float $uts, float $uas): float
{
    return round(($tugas * 0.2) + ($quiz * 0.2) + ($uts * 0.3) + ($uas * 0.3), 2);
}

function konversi_grade(float $nilai): string
{
    if ($nilai >= 85) return 'A';
    if ($nilai >= 70) return 'B';
    if ($nilai >= 55) return 'C';
    if ($nilai >= 40) return 'D';
    return 'E';
}

function bobot_grade(string $grade): float
{
    return match (strtoupper($grade)) {
        'A' => 4.0, 'B' => 3.0, 'C' => 2.0, 'D' => 1.0, default => 0.0,
    };
}

function hitung_ipk(string $nim): float
{
    $rows = db_fetch_all(
        "SELECT n.grade, mk.sks FROM nilai n
         JOIN mata_kuliah mk ON mk.kode = n.kode_mk
         WHERE n.nim = ? AND n.nilai_akhir > 0",
        's', [$nim]
    );
    if (!$rows) return 0.0;
    $totalBobot = 0;
    $totalSks = 0;
    foreach ($rows as $r) {
        $sks = (int)$r['sks'];
        $totalBobot += bobot_grade($r['grade']) * $sks;
        $totalSks += $sks;
    }
    return $totalSks > 0 ? round($totalBobot / $totalSks, 2) : 0.0;
}

function hitung_ips(string $nim, int $idTahun): float
{
    $rows = db_fetch_all(
        "SELECT n.grade, mk.sks FROM nilai n
         JOIN mata_kuliah mk ON mk.kode = n.kode_mk
         WHERE n.nim = ? AND n.id_tahun = ? AND n.nilai_akhir > 0",
        'si', [$nim, $idTahun]
    );
    if (!$rows) return 0.0;
    $totalBobot = 0;
    $totalSks = 0;
    foreach ($rows as $r) {
        $sks = (int)$r['sks'];
        $totalBobot += bobot_grade($r['grade']) * $sks;
        $totalSks += $sks;
    }
    return $totalSks > 0 ? round($totalBobot / $totalSks, 2) : 0.0;
}

function tahun_akademik_aktif(): ?array
{
    return db_fetch_one("SELECT * FROM tahun_akademik WHERE status = 'Aktif' ORDER BY id_tahun DESC LIMIT 1");
}

function pagination(int $total, int $page, int $perPage = 10): array
{
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    return compact('total', 'page', 'perPage', 'totalPages', 'offset');
}

function render_pagination(int $page, int $totalPages, string $path): string
{
    if ($totalPages <= 1) return '';
    $baseUrl = base_url($path);
    $html = '<nav class="pagination-glass"><ul class="pagination mb-0">';
    $qs = fn($p) => $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . $p;

    $html .= '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . ($page > 1 ? e($qs($page - 1)) : '#') . '">‹</a></li>';

    for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
        $active = $i === $page ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($qs($i)) . '">' . $i . '</a></li>';
    }

    $html .= '<li class="page-item' . ($page >= $totalPages ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . ($page < $totalPages ? e($qs($page + 1)) : '#') . '">›</a></li>';
    $html .= '</ul></nav>';
    return $html;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $path): void
{
    redirect_to($path);
}

function get_pengumuman_for_role(string $role): array
{
    if ($role === 'admin') {
        return db_fetch_all('SELECT * FROM pengumuman ORDER BY created_at DESC LIMIT 10');
    }
    $map = ['dosen' => 'Dosen', 'mahasiswa' => 'Mahasiswa'];
    $target = $map[$role] ?? 'Semua';
    return db_fetch_all(
        "SELECT * FROM pengumuman
         WHERE target_penerima IN ('Semua', ?)
         ORDER BY created_at DESC LIMIT 10",
        's', [$target]
    );
}

function create_notification(int $userId, string $pesan): void
{
    db_query('INSERT INTO notifikasi (user_id, pesan) VALUES (?, ?)', 'is', [$userId, $pesan]);
}

function notify_krs_status(string $nim, string $status, string $mk): void
{
    $mhs = db_fetch_one('SELECT user_id FROM mahasiswa WHERE nim = ?', 's', [$nim]);
    if ($mhs && $mhs['user_id']) {
        create_notification((int)$mhs['user_id'], "KRS $mk: status $status");
    }
}

function role_upload_subdir(string $role): string
{
    return match ($role) {
        'admin' => 'admin',
        'dosen' => 'dosen',
        'mahasiswa' => 'mahasiswa',
        default => '',
    };
}

function role_display_name(string $role): string
{
    return match ($role) {
        'admin' => 'Administrator',
        'dosen' => 'Dosen',
        'mahasiswa' => 'Mahasiswa',
        default => ucfirst($role),
    };
}

function user_subtitle(?array $user = null): string
{
    $user = $user ?? current_user();
    if (!$user) {
        return '';
    }
    return role_display_name($user['role'] ?? '');
}

function user_avatar_url(?array $user = null): string
{
    $user = $user ?? current_user();
    if (!$user) {
        return base_url('uploads/default.png');
    }

    $role = $user['role'] ?? '';
    $userId = (int)($user['id'] ?? 0);
    $foto = null;
    $subdir = role_upload_subdir($role);

    if ($role === 'admin') {
        $row = db_fetch_one('SELECT foto FROM admin WHERE user_id = ?', 'i', [$userId]);
        $foto = $row['foto'] ?? null;
    } elseif ($role === 'dosen') {
        $row = db_fetch_one('SELECT foto FROM dosen WHERE user_id = ?', 'i', [$userId]);
        $foto = $row['foto'] ?? null;
    } elseif ($role === 'mahasiswa') {
        $row = db_fetch_one('SELECT foto FROM mahasiswa WHERE user_id = ?', 'i', [$userId]);
        $foto = $row['foto'] ?? null;
    }

    if (!$foto || !$subdir) {
        return base_url('uploads/default.png');
    }

    return base_url('uploads/' . $subdir . '/' . $foto);
}

function upload_profile_photo(string $role, array $file, string $basename): ?string
{
    if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return null;
    }

    $subdir = role_upload_subdir($role);
    if ($subdir === '') {
        return null;
    }

    $dir = APP_ROOT . '/uploads/' . $subdir . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $fname = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename) . '_' . time() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dir . $fname)) {
        return $fname;
    }

    return null;
}

function change_user_password(int $userId, string $oldPassword, string $newPassword): bool
{
    if (strlen($newPassword) < 4) {
        return false;
    }

    $u = db_fetch_one('SELECT password FROM users WHERE id = ?', 'i', [$userId]);
    if (!$u) {
        return false;
    }

    $ok = ($oldPassword === $u['password']) || password_verify($oldPassword, $u['password']);
    if (!$ok) {
        return false;
    }

    db_query('UPDATE users SET password = ? WHERE id = ?', 'si', [$newPassword, $userId]);
    return true;
}

function create_user_account(string $username, string $role, string $status = 'Aktif'): int
{
    $exists = db_fetch_one('SELECT id FROM users WHERE username = ?', 's', [$username]);
    if ($exists) {
        throw new RuntimeException("Username $username sudah digunakan.");
    }
    db_query(
        'INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, ?)',
        'ssss',
        [$username, $username, $role, $status]
    );
    return db_insert_id();
}

function sync_user_username(int $userId, string $newUsername): void
{
    $exists = db_fetch_one('SELECT id FROM users WHERE username = ? AND id != ?', 'si', [$newUsername, $userId]);
    if ($exists) {
        throw new RuntimeException("Username $newUsername sudah digunakan.");
    }
    db_query('UPDATE users SET username = ? WHERE id = ?', 'si', [$newUsername, $userId]);
}

function delete_user_account(int $userId): void
{
    db_query('DELETE FROM notifikasi WHERE user_id = ?', 'i', [$userId]);
    db_query('DELETE FROM users WHERE id = ?', 'i', [$userId]);
}

function delete_mahasiswa_cascade(string $nim): void
{
    $m = db_fetch_one('SELECT user_id FROM mahasiswa WHERE nim = ?', 's', [$nim]);
    if (!$m) {
        return;
    }
    db_query('DELETE FROM presensi WHERE nim = ?', 's', [$nim]);
    db_query('DELETE FROM krs WHERE nim = ?', 's', [$nim]);
    db_query('DELETE FROM nilai WHERE nim = ?', 's', [$nim]);
    db_query('DELETE FROM mahasiswa WHERE nim = ?', 's', [$nim]);
    if ($m['user_id']) {
        delete_user_account((int)$m['user_id']);
    }
}

function delete_dosen_cascade(string $nidn): void
{
    $d = db_fetch_one('SELECT user_id FROM dosen WHERE nidn = ?', 's', [$nidn]);
    if (!$d) {
        return;
    }
    db_query('UPDATE mahasiswa SET id_dosen_wali = NULL WHERE id_dosen_wali = ?', 's', [$nidn]);
    db_query('DELETE FROM jadwal WHERE nidn = ?', 's', [$nidn]);
    db_query('DELETE FROM dosen WHERE nidn = ?', 's', [$nidn]);
    if ($d['user_id']) {
        delete_user_account((int)$d['user_id']);
    }
}

function ensure_dosen_user_account(string $nidn, string $status = 'Aktif'): int
{
    $dosen = db_fetch_one('SELECT user_id FROM dosen WHERE nidn = ?', 's', [$nidn]);
    if (!$dosen) {
        throw new RuntimeException('Data dosen tidak ditemukan.');
    }
    if ($dosen['user_id']) {
        return (int)$dosen['user_id'];
    }
    $userId = create_user_account($nidn, 'dosen', $status === 'Aktif' ? 'Aktif' : 'Nonaktif');
    db_query('UPDATE dosen SET user_id = ? WHERE nidn = ?', 'is', [$userId, $nidn]);
    return $userId;
}

function reset_user_password(int $userId): bool
{
    $user = db_fetch_one('SELECT id, username, role FROM users WHERE id = ?', 'i', [$userId]);
    if (!$user) {
        return false;
    }

    $default = $user['username'];
    if ($user['role'] === 'mahasiswa') {
        $m = db_fetch_one('SELECT nim FROM mahasiswa WHERE user_id = ?', 'i', [$userId]);
        $default = $m['nim'] ?? $default;
    } elseif ($user['role'] === 'dosen') {
        $d = db_fetch_one('SELECT nidn FROM dosen WHERE user_id = ?', 'i', [$userId]);
        $default = $d['nidn'] ?? $default;
    }

    db_query('UPDATE users SET password = ? WHERE id = ?', 'si', [$default, $userId]);
    create_notification($userId, 'Password Anda telah direset ke default.');
    return true;
}

function get_users_with_profiles(?string $search = ''): array
{
    $sql = "SELECT u.id, u.username, u.role, u.status, u.created_at,
                   COALESCE(m.nama, d.nama, a.nama, u.username) AS nama,
                   COALESCE(m.foto, d.foto, a.foto) AS foto,
                   CASE u.role
                       WHEN 'mahasiswa' THEN 'Mahasiswa'
                       WHEN 'dosen' THEN 'Dosen'
                       WHEN 'admin' THEN 'Admin'
                   END AS sumber
            FROM users u
            LEFT JOIN mahasiswa m ON m.user_id = u.id AND u.role = 'mahasiswa'
            LEFT JOIN dosen d ON d.user_id = u.id AND u.role = 'dosen'
            LEFT JOIN admin a ON a.user_id = u.id AND u.role = 'admin'";
    $params = [];
    $types = '';

    if ($search !== '') {
        $like = '%' . $search . '%';
        $sql .= " WHERE u.username LIKE ? OR COALESCE(m.nama, d.nama, a.nama, '') LIKE ? OR u.role LIKE ?";
        $params = [$like, $like, $like];
        $types = 'sss';
    }

    $sql .= ' ORDER BY u.role, u.username';
    return db_fetch_all($sql, $types, $params);
}

function refresh_session_profile(): void
{
    if (!is_logged_in()) {
        return;
    }
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $role = $_SESSION['role'] ?? '';
    $u = db_fetch_one('SELECT username, role, status FROM users WHERE id = ?', 'i', [$userId]);
    if (!$u || ($u['status'] ?? 'Aktif') === 'Nonaktif') {
        logout_user();
        redirect_to('index.php?err=nonaktif');
        exit;
    }

    $_SESSION['username'] = $u['username'];
    if ($role === 'mahasiswa') {
        $m = db_fetch_one('SELECT nama, nim FROM mahasiswa WHERE user_id = ?', 'i', [$userId]);
        if ($m) {
            $_SESSION['nama'] = $m['nama'];
            $_SESSION['nim'] = $m['nim'];
        }
    } elseif ($role === 'dosen') {
        $d = db_fetch_one('SELECT nama, nidn FROM dosen WHERE user_id = ?', 'i', [$userId]);
        if ($d) {
            $_SESSION['nama'] = $d['nama'];
            $_SESSION['nidn'] = $d['nidn'];
        }
    } elseif ($role === 'admin') {
        $a = db_fetch_one('SELECT nama FROM admin WHERE user_id = ?', 'i', [$userId]);
        if ($a) {
            $_SESSION['nama'] = $a['nama'];
        }
    }
}

function ensure_jadwal_mk(string $kodeMk, string $nidn, ?int $idTahun = null): void
{
    if ($nidn === '') {
        return;
    }

    $ta = $idTahun ? ['id_tahun' => $idTahun] : tahun_akademik_aktif();
    if (!$ta) {
        return;
    }

    $idTahun = (int)$ta['id_tahun'];
    $exists = db_fetch_one(
        'SELECT id_jadwal FROM jadwal WHERE kode_mk = ? AND nidn = ? AND id_tahun = ?',
        'ssi',
        [$kodeMk, $nidn, $idTahun]
    );

    if (!$exists) {
        db_query(
            'INSERT INTO jadwal (kode_mk, nidn, id_tahun, hari, jam_mulai, jam_selesai, ruangan) VALUES (?,?,?,?,?,?,?)',
            'ssissss',
            [$kodeMk, $nidn, $idTahun, 'Senin', '08:00:00', '10:00:00', 'Ruang Kuliah']
        );
    }
}
