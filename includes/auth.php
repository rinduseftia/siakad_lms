<?php
/**
 * Autentikasi & otorisasi session
 */
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['logged_in']);
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'] ?? 0,
        'username' => $_SESSION['username'] ?? '',
        'role'     => $_SESSION['role'] ?? '',
        'nama'     => $_SESSION['nama'] ?? '',
        'nim'      => $_SESSION['nim'] ?? '',
    ];
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect_to('index.php?err=login');
        exit;
    }
    require_once __DIR__ . '/functions.php';
    refresh_session_profile();
}

function require_role(string ...$roles): void
{
    require_login();
    $user = current_user();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        die('Akses ditolak.');
    }
}

function login_user(string $username, string $password): ?array
{
    $user = db_fetch_one(
        'SELECT id, username, password, role, status FROM users WHERE username = ? LIMIT 1',
        's',
        [$username]
    );

    if (!$user) {
        return null;
    }

    if (($user['status'] ?? 'Aktif') === 'Nonaktif') {
        $_SESSION['login_error'] = 'nonaktif';
        return null;
    }

    $ok = ($password === $user['password']) || password_verify($password, $user['password']);
    if (!$ok) {
        return null;
    }

    $nama = $user['username'];
    if ($user['role'] === 'mahasiswa') {
        $m = db_fetch_one('SELECT nama, nim FROM mahasiswa WHERE user_id = ?', 'i', [$user['id']]);
        if ($m) {
            $nama = $m['nama'];
            $user['nim'] = $m['nim'];
        }
    } elseif ($user['role'] === 'dosen') {
        $d = db_fetch_one('SELECT nama, nidn FROM dosen WHERE user_id = ?', 'i', [$user['id']]);
        if ($d) {
            $nama = $d['nama'];
            $user['nidn'] = $d['nidn'];
        }
    } elseif ($user['role'] === 'admin') {
        $a = db_fetch_one('SELECT nama FROM admin WHERE user_id = ?', 'i', [$user['id']]);
        if ($a) {
            $nama = $a['nama'];
        }
    }

    session_regenerate_id(true);
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['nama']      = $nama;
    $_SESSION['nim']       = $user['nim'] ?? $user['username'];
    $_SESSION['nidn']      = $user['nidn'] ?? '';

    audit_log($user['id'], 'Login berhasil');
    return current_user();
}

function logout_user(): void
{
    if (is_logged_in()) {
        audit_log($_SESSION['user_id'] ?? null, 'Logout');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function audit_log(?int $userId, string $aktivitas): void
{
    db_query(
        'INSERT INTO audit_log (user_id, aktivitas) VALUES (?, ?)',
        'is',
        [$userId, $aktivitas]
    );
}
