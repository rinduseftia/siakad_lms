<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

$kode = $_GET['kode'] ?? '';
$edit = $kode ? db_fetch_one('SELECT * FROM mata_kuliah WHERE kode = ?', 's', [$kode]) : null;
$dosenList = db_fetch_all("SELECT nidn, nama FROM dosen WHERE status = 'Aktif' ORDER BY nama");
$ta = tahun_akademik_aktif();

$jadwalDosen = '';
if ($edit && $ta) {
    $jd = db_fetch_one(
        'SELECT nidn FROM jadwal WHERE kode_mk = ? AND id_tahun = ? LIMIT 1',
        'si',
        [$kode, (int)$ta['id_tahun']]
    );
    $jadwalDosen = $jd['nidn'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = [
        'kode' => trim($_POST['kode']),
        'nama' => trim($_POST['nama']),
        'sks' => (int)$_POST['sks'],
        'semester' => (int)$_POST['semester'],
        'status' => $_POST['status'],
    ];
    $nidn = trim($_POST['nidn'] ?? '');

    try {
        if ($edit) {
            db_query(
                'UPDATE mata_kuliah SET nama=?, sks=?, semester=?, status=? WHERE kode=?',
                'siiss',
                [$d['nama'], $d['sks'], $d['semester'], $d['status'], $d['kode']]
            );
        } else {
            db_query(
                'INSERT INTO mata_kuliah (kode, nama, sks, semester, status) VALUES (?,?,?,?,?)',
                'ssiis',
                [$d['kode'], $d['nama'], $d['sks'], $d['semester'], $d['status']]
            );
        }

        if ($nidn !== '') {
            ensure_jadwal_mk($d['kode'], $nidn);
            flash('success', 'Mata kuliah disimpan. Jadwal dosen otomatis dibuat/diperbarui.');
        } else {
            flash('success', 'Mata kuliah disimpan. Pilih dosen pengampu agar muncul di portal dosen.');
        }
        audit_log(current_user()['id'], ($edit ? 'Edit' : 'Tambah') . ' mata kuliah ' . $d['kode']);
        redirect('admin/matakuliah.php');
    } catch (RuntimeException $ex) {
        flash('danger', 'Gagal menyimpan: ' . $ex->getMessage());
    }
}

render_layout_start('Form Mata Kuliah', 'admin', admin_menu('matakuliah'));
?>
<div class="form-panel">
<div class="glass-card form-genz">
<form method="post" class="row g-3">
    <div class="col-12"><label>Kode MK</label><input name="kode" class="form-control" value="<?= e($edit['kode'] ?? '') ?>" <?= $edit ? 'readonly' : '' ?> required></div>
    <div class="col-12"><label>Nama MK</label><input name="nama" class="form-control" value="<?= e($edit['nama'] ?? '') ?>" required></div>
    <div class="col-6"><label>SKS</label><input name="sks" type="number" class="form-control" value="<?= e($edit['sks'] ?? '3') ?>"></div>
    <div class="col-6"><label>Semester</label><input name="semester" type="number" class="form-control" value="<?= e($edit['semester'] ?? '1') ?>"></div>
    <div class="col-12"><label>Dosen Pengampu</label>
        <select name="nidn" class="form-select">
            <option value="">— Pilih Dosen —</option>
            <?php foreach ($dosenList as $ds): ?>
            <option value="<?= e($ds['nidn']) ?>" <?= $jadwalDosen === $ds['nidn'] ? 'selected' : '' ?>><?= e($ds['nama']) ?> (<?= e($ds['nidn']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted">Wajib dipilih agar MK muncul di portal dosen (nilai, presensi). Jadwal detail bisa diatur di menu Jadwal Kuliah.</small>
    </div>
    <div class="col-12"><label>Status</label>
        <select name="status" class="form-select">
            <option value="Aktif" <?= ($edit['status'] ?? 'Aktif') === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="Nonaktif" <?= ($edit['status'] ?? '') === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
    </div>
    <div class="col-12 d-flex gap-2 mt-2">
        <button type="submit" class="btn-genz">Simpan</button>
        <a href="matakuliah.php" class="btn-genz btn-genz-outline">Batal</a>
    </div>
</form>
</div>
</div>
<?php render_layout_end(); ?>
