<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

$nidn = $_GET['nidn'] ?? '';
$edit = $nidn ? db_fetch_one('SELECT * FROM dosen WHERE nidn = ?', 's', [$nidn]) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nidn' => trim($_POST['nidn']),
        'nama' => trim($_POST['nama']),
        'email' => trim($_POST['email']),
        'agama' => trim($_POST['agama']),
        'jabatan' => trim($_POST['jabatan']),
        'status' => $_POST['status'],
        'jk' => trim($_POST['jk']),
        'tmp_lahir' => trim($_POST['tmp_lahir']),
        'tgl_lahir' => $_POST['tgl_lahir'] ?: null,
        'alamat' => trim($_POST['alamat']),
        'no_hp' => trim($_POST['no_hp']),
    ];

    try {
        if ($edit) {
            $oldNidn = $edit['nidn'];
            if ($data['nidn'] !== $oldNidn) {
                db_query('UPDATE jadwal SET nidn=? WHERE nidn=?', 'ss', [$data['nidn'], $oldNidn]);
                db_query('UPDATE mahasiswa SET id_dosen_wali=? WHERE id_dosen_wali=?', 'ss', [$data['nidn'], $oldNidn]);
                if ($edit['user_id']) {
                    sync_user_username((int)$edit['user_id'], $data['nidn']);
                }
            }
            db_query(
                "UPDATE dosen SET nidn=?, nama=?, email=?, agama=?, jabatan=?, status=?, jk=?, tmp_lahir=?, tgl_lahir=?, alamat=?, no_hp=? WHERE nidn=?",
                'ssssssssssss',
                [$data['nidn'], $data['nama'], $data['email'], $data['agama'], $data['jabatan'], $data['status'],
                 $data['jk'], $data['tmp_lahir'], $data['tgl_lahir'], $data['alamat'], $data['no_hp'], $oldNidn]
            );
            if ($edit['user_id']) {
                db_query(
                    "UPDATE users SET status = ? WHERE id = ?",
                    'si',
                    [$data['status'] === 'Aktif' ? 'Aktif' : 'Nonaktif', (int)$edit['user_id']]
                );
            } else {
                ensure_dosen_user_account($data['nidn'], $data['status']);
            }
            flash('success', 'Dosen diperbarui.');
        } else {
            $userStatus = $data['status'] === 'Aktif' ? 'Aktif' : 'Nonaktif';
            $uid = create_user_account($data['nidn'], 'dosen', $userStatus);
            db_query(
                "INSERT INTO dosen (user_id,nidn,nama,email,agama,jabatan,status,jk,tmp_lahir,tgl_lahir,alamat,no_hp) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                'isssssssssss',
                [$uid, $data['nidn'], $data['nama'], $data['email'], $data['agama'], $data['jabatan'],
                 $data['status'], $data['jk'], $data['tmp_lahir'], $data['tgl_lahir'], $data['alamat'], $data['no_hp']]
            );
            flash('success', 'Dosen ditambahkan. Akun login: username & password = NIDN.');
        }
        audit_log(current_user()['id'], ($edit ? 'Edit' : 'Tambah') . ' dosen ' . $data['nidn']);
        redirect('admin/dosen.php');
    } catch (RuntimeException $ex) {
        flash('danger', $ex->getMessage());
    }
}

render_layout_start($edit ? 'Edit Dosen' : 'Tambah Dosen', 'admin', admin_menu('dosen'));
?>
<div class="form-panel">
<div class="glass-card form-genz">
<form method="post" class="row g-3">
    <div class="col-md-6"><label>NIDN</label><input name="nidn" class="form-control" value="<?= e($edit['nidn'] ?? '') ?>" required></div>
    <div class="col-md-6"><label>Nama</label><input name="nama" class="form-control" value="<?= e($edit['nama'] ?? '') ?>" required></div>
    <div class="col-md-6"><label>Email</label><input name="email" class="form-control" value="<?= e($edit['email'] ?? '') ?>"></div>
    <div class="col-md-6"><label>Jabatan</label><input name="jabatan" class="form-control" value="<?= e($edit['jabatan'] ?? 'Dosen') ?>"></div>
    <div class="col-md-4"><label>Agama</label><input name="agama" class="form-control" value="<?= e($edit['agama'] ?? '') ?>"></div>
    <div class="col-md-4"><label>Gender</label>
        <select name="jk" class="form-select">
            <option <?= ($edit['jk'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
            <option <?= ($edit['jk'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
        </select>
    </div>
    <div class="col-md-4"><label>Status</label>
        <select name="status" class="form-select">
            <option <?= ($edit['status'] ?? 'Aktif') === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
            <option <?= ($edit['status'] ?? '') === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
    </div>
    <div class="col-md-6"><label>Tempat Lahir</label><input name="tmp_lahir" class="form-control" value="<?= e($edit['tmp_lahir'] ?? '') ?>"></div>
    <div class="col-md-6"><label>Tgl Lahir</label><input name="tgl_lahir" type="date" class="form-control" value="<?= e($edit['tgl_lahir'] ?? '') ?>"></div>
    <div class="col-md-6"><label>No HP</label><input name="no_hp" class="form-control" value="<?= e($edit['no_hp'] ?? '') ?>"></div>
    <div class="col-12"><label>Alamat</label><textarea name="alamat" class="form-control"><?= e($edit['alamat'] ?? '') ?></textarea></div>
    <?php if (!$edit): ?>
    <div class="col-12"><p class="small text-muted mb-0">Akun login akan dibuat otomatis: username = NIDN, password default = NIDN.</p></div>
    <?php endif; ?>
    <div class="col-12 d-flex gap-2"><button type="submit" class="btn-genz">Simpan</button> <a href="dosen.php" class="btn-genz btn-genz-outline">Batal</a></div>
</form>
</div>
</div>
<?php render_layout_end(); ?>
