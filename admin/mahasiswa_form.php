<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

$nim = $_GET['nim'] ?? '';
$edit = $nim ? db_fetch_one('SELECT * FROM mahasiswa WHERE nim = ?', 's', [$nim]) : null;
$dosenList = db_fetch_all("SELECT nidn, nama FROM dosen WHERE status = 'Aktif' ORDER BY nama");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nim' => trim($_POST['nim']),
        'nama' => trim($_POST['nama']),
        'jur' => trim($_POST['jur']),
        'program_studi' => trim($_POST['program_studi']),
        'angkatan' => (int)$_POST['angkatan'],
        'semester_sekarang' => (int)$_POST['semester_sekarang'],
        'id_dosen_wali' => $_POST['id_dosen_wali'] ?: null,
        'status' => $_POST['status'],
        'email' => trim($_POST['email']),
        'agama' => trim($_POST['agama']),
        'jk' => trim($_POST['jk']),
        'tmp_lahir' => trim($_POST['tmp_lahir']),
        'tgl_lahir' => $_POST['tgl_lahir'] ?: null,
        'alamat' => trim($_POST['alamat']),
    ];

    try {
        if ($edit) {
            $oldNim = $edit['nim'];
            if ($data['nim'] !== $oldNim) {
                db_query('UPDATE krs SET nim=? WHERE nim=?', 'ss', [$data['nim'], $oldNim]);
                db_query('UPDATE nilai SET nim=? WHERE nim=?', 'ss', [$data['nim'], $oldNim]);
                db_query('UPDATE presensi SET nim=? WHERE nim=?', 'ss', [$data['nim'], $oldNim]);
                if ($edit['user_id']) {
                    sync_user_username((int)$edit['user_id'], $data['nim']);
                }
            }
            db_query(
                "UPDATE mahasiswa SET nim=?, nama=?, jur=?, program_studi=?, angkatan=?, semester_sekarang=?,
                 id_dosen_wali=?, status=?, email=?, agama=?, jk=?, tmp_lahir=?, tgl_lahir=?, alamat=? WHERE nim=?",
                'ssssiisssssssss',
                [$data['nim'], $data['nama'], $data['jur'], $data['program_studi'], $data['angkatan'], $data['semester_sekarang'],
                 $data['id_dosen_wali'], $data['status'], $data['email'], $data['agama'], $data['jk'],
                 $data['tmp_lahir'], $data['tgl_lahir'], $data['alamat'], $oldNim]
            );
            if ($edit['user_id']) {
                db_query(
                    'UPDATE users SET status=? WHERE id=?',
                    'si',
                    [$data['status'] === 'Aktif' ? 'Aktif' : 'Nonaktif', (int)$edit['user_id']]
                );
            }
            flash('success', 'Data mahasiswa diperbarui.');
        } else {
            $userStatus = $data['status'] === 'Aktif' ? 'Aktif' : 'Nonaktif';
            $userId = create_user_account($data['nim'], 'mahasiswa', $userStatus);
            db_query(
                "INSERT INTO mahasiswa (nim,user_id,nama,jur,program_studi,angkatan,semester_sekarang,id_dosen_wali,status,email,agama,jk,tmp_lahir,tgl_lahir,alamat)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                'sisssiissssssss',
                [$data['nim'], $userId, $data['nama'], $data['jur'], $data['program_studi'], $data['angkatan'],
                 $data['semester_sekarang'], $data['id_dosen_wali'], $data['status'], $data['email'], $data['agama'],
                 $data['jk'], $data['tmp_lahir'], $data['tgl_lahir'], $data['alamat']]
            );
            flash('success', 'Mahasiswa baru ditambahkan. Akun login: username & password = NIM.');
        }
        audit_log(current_user()['id'], ($edit ? 'Edit' : 'Tambah') . ' mahasiswa ' . $data['nim']);
        redirect('admin/mahasiswa.php');
    } catch (RuntimeException $ex) {
        flash('danger', $ex->getMessage());
    }
}

render_layout_start($edit ? 'Edit Mahasiswa' : 'Tambah Mahasiswa', 'admin', admin_menu('mahasiswa'));
?>
<div class="form-panel">
<div class="glass-card form-genz">
<form method="post">
    <div class="row g-3">
        <div class="col-md-6"><label>NIM</label><input name="nim" class="form-control" value="<?= e($edit['nim'] ?? '') ?>" required></div>
        <div class="col-md-6"><label>Nama</label><input name="nama" class="form-control" value="<?= e($edit['nama'] ?? '') ?>" required></div>
        <div class="col-md-6"><label>Jurusan</label><input name="jur" class="form-control" value="<?= e($edit['jur'] ?? 'Informatika') ?>"></div>
        <div class="col-md-6"><label>Prodi</label><input name="program_studi" class="form-control" value="<?= e($edit['program_studi'] ?? 'Sistem Informasi') ?>"></div>
        <div class="col-md-4"><label>Angkatan</label><input name="angkatan" type="number" class="form-control" value="<?= e($edit['angkatan'] ?? date('Y')) ?>"></div>
        <div class="col-md-4"><label>Semester</label><input name="semester_sekarang" type="number" min="1" max="14" class="form-control" value="<?= e($edit['semester_sekarang'] ?? '1') ?>"></div>
        <div class="col-md-4"><label>Dosen Wali</label>
            <select name="id_dosen_wali" class="form-select">
                <option value="">— Pilih —</option>
                <?php foreach ($dosenList as $d): ?>
                <option value="<?= e($d['nidn']) ?>" <?= ($edit['id_dosen_wali'] ?? '') === $d['nidn'] ? 'selected' : '' ?>><?= e($d['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6"><label>Email</label><input name="email" type="email" class="form-control" value="<?= e($edit['email'] ?? '') ?>"></div>
        <div class="col-md-6"><label>Status</label><select name="status" class="form-select"><option <?= ($edit['status'] ?? '') === 'Aktif' ? 'selected' : '' ?>>Aktif</option><option <?= ($edit['status'] ?? '') === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option></select></div>
        <div class="col-md-4"><label>Agama</label><input name="agama" class="form-control" value="<?= e($edit['agama'] ?? '') ?>"></div>
        <div class="col-md-4"><label>Jenis Kelamin</label><select name="jk" class="form-select"><option <?= ($edit['jk'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option><option <?= ($edit['jk'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option></select></div>
        <div class="col-md-4"><label>Tgl Lahir</label><input name="tgl_lahir" type="date" class="form-control" value="<?= e($edit['tgl_lahir'] ?? '') ?>"></div>
        <div class="col-md-6"><label>Tempat Lahir</label><input name="tmp_lahir" class="form-control" value="<?= e($edit['tmp_lahir'] ?? '') ?>"></div>
        <div class="col-12"><label>Alamat</label><textarea name="alamat" class="form-control" rows="2"><?= e($edit['alamat'] ?? '') ?></textarea></div>
    </div>
    <?php if (!$edit): ?>
    <p class="small text-muted mt-3 mb-0">Akun login akan dibuat otomatis: username = NIM, password default = NIM.</p>
    <?php endif; ?>
    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn-genz">Simpan</button>
        <a href="mahasiswa.php" class="btn-genz btn-genz-outline">Batal</a>
    </div>
</form>
</div>
</div>
<?php render_layout_end(); ?>
