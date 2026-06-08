<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    db_query(
        'INSERT INTO pengumuman (judul, isi, target_penerima, dibuat_oleh) VALUES (?,?,?,?)',
        'sssi',
        [trim($_POST['judul']), trim($_POST['isi']), $_POST['target_penerima'], current_user()['id']]
    );
    flash('success', 'Pengumuman dipublikasikan.');
    redirect('admin/pengumuman.php');
}
if (isset($_GET['hapus'])) {
    db_query('DELETE FROM pengumuman WHERE id_pengumuman=?', 'i', [(int)$_GET['hapus']]);
    flash('success', 'Pengumuman dihapus.');
    redirect('admin/pengumuman.php');
}

$rows = db_fetch_all('SELECT * FROM pengumuman ORDER BY created_at DESC');

render_layout_start('Pengumuman', 'admin', admin_menu('pengumuman'));
?>
<div class="row g-3">
<div class="col-lg-4">
<div class="glass-card form-genz">
<p class="genz-title">Buat Pengumuman</p>
<form method="post">
    <div class="mb-2"><label>Judul</label><input name="judul" class="form-control" required></div>
    <div class="mb-2"><label>Isi</label><textarea name="isi" class="form-control" rows="4" required></textarea></div>
    <div class="mb-3"><label>Target</label>
        <select name="target_penerima" class="form-select">
            <option>Semua</option><option>Dosen</option><option>Mahasiswa</option>
        </select>
        <small class="text-muted">Filter: pengumuman Mahasiswa tidak tampil untuk Dosen</small>
    </div>
    <button class="btn-genz w-100">Publikasikan</button>
</form>
</div>
</div>
<div class="col-lg-8">
<div class="glass-card">
<?php foreach ($rows as $r): ?>
<div class="mb-3 pb-3 border-bottom">
    <div class="d-flex justify-content-between"><strong><?= e($r['judul']) ?></strong>
        <?= status_badge($r['target_penerima']) ?></div>
    <p class="small mb-1"><?= nl2br(e($r['isi'])) ?></p>
    <div class="d-flex justify-content-between"><small class="text-muted"><?= e($r['created_at']) ?></small>
    <a href="?hapus=<?= $r['id_pengumuman'] ?>" class="btn-genz btn-genz-danger btn-genz-sm" onclick="return confirm('Hapus pengumuman ini?')">Hapus</a></div>
</div>
<?php endforeach; ?>
</div>
</div>
</div>
<?php render_layout_end(); ?>
