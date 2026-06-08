<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('mahasiswa');

$user = current_user();
$mhs = db_fetch_one('SELECT * FROM mahasiswa WHERE user_id = ?', 'i', [$user['id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'profil') {
        db_query(
            'UPDATE mahasiswa SET nama = ?, email = ?, no_hp = ?, alamat = ? WHERE nim = ?',
            'sssss',
            [trim($_POST['nama']), trim($_POST['email']), trim($_POST['no_hp'] ?? ''), trim($_POST['alamat']), $mhs['nim']]
        );
        $_SESSION['nama'] = trim($_POST['nama']);
        flash('success', 'Profil berhasil diperbarui.');
    } elseif ($action === 'password') {
        if (change_user_password($user['id'], $_POST['old_password'] ?? '', $_POST['new_password'] ?? '')) {
            flash('success', 'Password berhasil diubah.');
        } else {
            flash('danger', 'Password lama salah atau password baru terlalu pendek.');
        }
    } elseif ($action === 'foto') {
        $fname = upload_profile_photo('mahasiswa', $_FILES['foto'] ?? [], 'mhs_' . $mhs['nim']);
        if ($fname) {
            db_query('UPDATE mahasiswa SET foto = ? WHERE nim = ?', 'ss', [$fname, $mhs['nim']]);
            flash('success', 'Foto profil diperbarui.');
        } else {
            flash('danger', 'Upload foto gagal. Gunakan JPG, PNG, atau WEBP.');
        }
    }

    redirect('mahasiswa/profil.php');
}

$mhs = db_fetch_one('SELECT * FROM mahasiswa WHERE user_id = ?', 'i', [$user['id']]);

render_layout_start('Profil Saya', 'mahasiswa', mhs_menu('profil'));
?>
<div class="row g-3">
<div class="col-lg-5">
<div class="glass-card text-center">
    <img src="<?= e(user_avatar_url($user)) ?>" alt="Avatar" class="profile-avatar mb-3">
    <h5 class="fw-bold"><?= e($mhs['nama']) ?></h5>
    <p class="text-muted"><?= e($mhs['nim']) ?> · <?= e($mhs['program_studi']) ?></p>
    <form method="post" enctype="multipart/form-data" class="form-genz">
        <input type="hidden" name="action" value="foto">
        <input type="file" name="foto" accept="image/*" class="form-control mb-2">
        <button class="btn-genz btn-genz-outline btn-genz-sm">Upload Foto</button>
    </form>
</div>
</div>
<div class="col-lg-7">
<div class="glass-card form-genz mb-3">
    <p class="genz-title">Data Profil</p>
    <form method="post">
        <input type="hidden" name="action" value="profil">
        <div class="mb-2"><label>NIM</label><input class="form-control" value="<?= e($mhs['nim']) ?>" readonly></div>
        <div class="mb-2"><label>Nama</label><input name="nama" class="form-control" value="<?= e($mhs['nama']) ?>" required></div>
        <div class="mb-2"><label>Email</label><input name="email" type="email" class="form-control" value="<?= e($mhs['email']) ?>"></div>
        <div class="mb-2"><label>No. HP</label><input name="no_hp" class="form-control" value="<?= e($mhs['no_hp'] ?? '') ?>"></div>
        <div class="mb-2"><label>Alamat</label><textarea name="alamat" class="form-control" rows="2"><?= e($mhs['alamat']) ?></textarea></div>
        <div class="row g-2 small mb-2">
            <div class="col-6"><strong>Agama</strong><br><?= e($mhs['agama']) ?></div>
            <div class="col-6"><strong>JK</strong><br><?= e($mhs['jk']) ?></div>
            <div class="col-6"><strong>Semester</strong><br><?= e($mhs['semester_sekarang']) ?></div>
            <div class="col-6"><strong>Prodi</strong><br><?= e($mhs['program_studi']) ?></div>
        </div>
        <button class="btn-genz">Simpan Profil</button>
    </form>
</div>
<div class="glass-card form-genz">
    <p class="genz-title">Ganti Password</p>
    <form method="post">
        <input type="hidden" name="action" value="password">
        <div class="mb-2"><label>Password Lama</label><input type="password" name="old_password" class="form-control" required></div>
        <div class="mb-2"><label>Password Baru</label><input type="password" name="new_password" class="form-control" required minlength="4"></div>
        <button class="btn-genz">Simpan Password</button>
    </form>
</div>
</div>
</div>
<?php render_layout_end(); ?>
