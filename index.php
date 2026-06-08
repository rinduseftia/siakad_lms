<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect(current_user()['role'] . '/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $user = login_user($username, $password);
        if ($user) {
            redirect($user['role'] . '/dashboard.php');
        }
        if (($_SESSION['login_error'] ?? '') === 'nonaktif') {
            $error = 'Akun Anda dinonaktifkan. Hubungi administrator.';
            unset($_SESSION['login_error']);
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Lengkapi username dan password.';
    }
}
if (isset($_GET['err'])) {
    $error = match ($_GET['err']) {
        'nonaktif' => 'Akun Anda dinonaktifkan. Hubungi administrator.',
        default => 'Silakan login terlebih dahulu.',
    };
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SIAKAD Gen-Z</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/genz-glass.css') ?>">
</head>
<body class="login-genz">
    <div class="login-box">
        <p class="genz-title text-center">Sistem Informasi Akademik</p>
        <h1 class="text-center fw-bold mb-1">SIAKAD LMS</h1>
        <p class="text-center text-muted mb-4 small">Portal Akademik Kampus</p>
        <?php if ($error): ?><div class="alert-genz danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form-genz">
            <div class="mb-3">
                <label>Username / NIM / NIDN</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-4">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn-genz w-100 justify-content-center">Masuk</button>
        </form>
        <p class="text-center mt-3 small text-muted">Admin: admin / 12345</p>
        <div class="dark-toggle justify-content-center mt-3">
            <label><input type="checkbox" id="darkModeToggle"> Mode Gelap</label>
        </div>
    </div>
    <script src="<?= asset('js/dark-mode.js') ?>"></script>
</body>
</html>
