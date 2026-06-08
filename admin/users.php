<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

$search = trim($_GET['q'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid = (int)($_POST['user_id'] ?? 0);

    if ($action === 'reset_password' && $uid) {
        if (reset_user_password($uid)) {
            $user = db_fetch_one('SELECT username FROM users WHERE id=?', 'i', [$uid]);
            flash('success', 'Password direset untuk ' . ($user['username'] ?? ''));
            audit_log(current_user()['id'], 'Reset password user #' . $uid);
        } else {
            flash('danger', 'Reset password gagal.');
        }
    } elseif ($action === 'toggle_status' && $uid) {
        $user = db_fetch_one('SELECT id, status, role FROM users WHERE id=?', 'i', [$uid]);
        if ($user && $user['role'] !== 'admin') {
            $newStatus = ($user['status'] ?? 'Aktif') === 'Aktif' ? 'Nonaktif' : 'Aktif';
            db_query('UPDATE users SET status=? WHERE id=?', 'si', [$newStatus, $uid]);
            flash('success', 'Status akun diubah menjadi ' . $newStatus);
            audit_log(current_user()['id'], "Ubah status user #$uid → $newStatus");
        } else {
            flash('danger', 'Status akun admin tidak dapat dinonaktifkan dari sini.');
        }
    }
    redirect('admin/users.php' . ($search ? '?q=' . urlencode($search) : ''));
}

$rows = get_users_with_profiles($search);

render_layout_start('User Management', 'admin', admin_menu('users'));
?>
<div class="page-toolbar">
    <form method="get" class="d-flex gap-2 flex-grow-1" style="max-width:400px">
        <input type="search" name="q" class="form-control" placeholder="Cari username, nama, role..." value="<?= e($search) ?>">
        <button class="btn-genz btn-genz-outline btn-genz-sm">Cari</button>
        <?php if ($search): ?><a href="users.php" class="btn-genz btn-genz-outline btn-genz-sm">Reset</a><?php endif; ?>
    </form>
    <span class="toolbar-spacer"></span>
    <span class="small text-muted"><?= count($rows) ?> akun terdaftar</span>
</div>
<p class="small text-muted mb-3">Akun dibuat otomatis saat menambah mahasiswa/dosen. Halaman ini hanya untuk monitoring, reset password, dan aktivasi akun.</p>
<div class="glass-card p-0 overflow-hidden">
    <div class="table-wrap">
        <table class="table-glass">
            <thead>
                <tr>
                    <th>ID</th><th>Avatar</th><th>Username</th><th>Nama</th><th>Role</th>
                    <th>Sumber Data</th><th>Status</th><th>Terdaftar</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $avatar = $r['foto']
                    ? base_url('uploads/' . role_upload_subdir($r['role']) . '/' . $r['foto'])
                    : base_url('uploads/default.png');
            ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><img src="<?= e($avatar) ?>" alt="" class="avatar-sm"></td>
                <td><strong><?= e($r['username']) ?></strong></td>
                <td><?= e($r['nama']) ?></td>
                <td><?= status_badge($r['role']) ?></td>
                <td><?= e($r['sumber']) ?></td>
                <td><?= status_badge($r['status'] ?? 'Aktif') ?></td>
                <td><?= e($r['created_at']) ?></td>
                <td class="td-actions">
                    <form method="post" class="d-inline" onsubmit="return confirm('Reset password ke default?')">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" value="<?= $r['id'] ?>">
                        <button class="btn-genz btn-genz-outline btn-genz-sm">Reset PW</button>
                    </form>
                    <?php if ($r['role'] !== 'admin'): ?>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" value="<?= $r['id'] ?>">
                        <button class="btn-genz btn-genz-sm <?= ($r['status'] ?? 'Aktif') === 'Aktif' ? 'btn-genz-danger' : 'btn-genz-success' ?>">
                            <?= ($r['status'] ?? 'Aktif') === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada akun ditemukan</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php render_layout_end(); ?>
