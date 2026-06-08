<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('mahasiswa');

$user = current_user();
$mhs = db_fetch_one('SELECT * FROM mahasiswa WHERE user_id=?', 'i', [$user['id']]);
$nim = $mhs['nim'];
$semester = (int)($mhs['semester_sekarang'] ?? 1);
$ta = tahun_akademik_aktif();
$idTahun = (int)($ta['id_tahun'] ?? 0);
$noTahunAktif = !$ta;

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $krs = db_fetch_one("SELECT * FROM krs WHERE id_krs=? AND nim=? AND status_krs='Pending'", 'is', [$id, $nim]);
    if ($krs) {
        db_query('DELETE FROM krs WHERE id_krs=?', 'i', [$id]);
        flash('success', 'Mata kuliah dihapus dari KRS.');
    }
    redirect('mahasiswa/krs.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ambil_mk') {
    if (!$idTahun) {
        flash('danger', 'Tidak ada tahun akademik aktif. Hubungi admin.');
        redirect('mahasiswa/krs.php');
    }
    $kode = trim($_POST['kode_mk'] ?? '');
    if ($kode === '') {
        flash('danger', 'Pilih mata kuliah terlebih dahulu.');
        redirect('mahasiswa/krs.php');
    }
    $mk = db_fetch_one("SELECT * FROM mata_kuliah WHERE kode=? AND status='Aktif'", 's', [$kode]);
    if (!$mk) {
        flash('danger', 'Mata kuliah tidak ditemukan atau nonaktif.');
        redirect('mahasiswa/krs.php');
    }
    $exists = db_fetch_one('SELECT id_krs FROM krs WHERE nim=? AND kode_mk=? AND id_tahun=?', 'ssi', [$nim, $kode, $idTahun]);
    if (!$exists) {
        db_query('INSERT INTO krs (nim,kode_mk,id_tahun,status_krs) VALUES (?,?,?,?)', 'ssis', [$nim, $kode, $idTahun, 'Pending']);
        if ($mhs['id_dosen_wali']) {
            $dosen = db_fetch_one('SELECT user_id FROM dosen WHERE nidn=?', 's', [$mhs['id_dosen_wali']]);
            if ($dosen && $dosen['user_id']) {
                create_notification((int)$dosen['user_id'], "KRS baru dari $nim — MK $kode menunggu persetujuan");
            }
        }
        $admins = db_fetch_all("SELECT id FROM users WHERE role='admin' AND status='Aktif'");
        foreach ($admins as $adm) {
            create_notification((int)$adm['id'], "KRS baru $nim — $kode menunggu persetujuan");
        }
        flash('success', 'Mata kuliah ' . $mk['nama'] . ' ditambahkan ke KRS (Pending).');
    } else {
        flash('danger', 'Mata kuliah sudah ada di KRS semester ini.');
    }
    redirect('mahasiswa/krs.php');
}

$krsList = db_fetch_all(
    "SELECT k.*, mk.nama AS nama_mk, mk.sks FROM krs k JOIN mata_kuliah mk ON mk.kode=k.kode_mk
     WHERE k.nim=? AND k.id_tahun=? ORDER BY k.id_krs",
    'si', [$nim, $idTahun]
);

$mkTersedia = db_fetch_all(
    "SELECT mk.*, GROUP_CONCAT(DISTINCT d.nama ORDER BY d.nama SEPARATOR ', ') AS nama_dosen
     FROM mata_kuliah mk
     LEFT JOIN jadwal j ON j.kode_mk = mk.kode AND j.id_tahun = ?
     LEFT JOIN dosen d ON d.nidn = j.nidn
     WHERE mk.semester = ? AND mk.status = 'Aktif'
     AND mk.kode NOT IN (SELECT kode_mk FROM krs WHERE nim = ? AND id_tahun = ?)
     GROUP BY mk.id
     ORDER BY mk.kode",
    'isii',
    [$idTahun, $semester, $nim, $idTahun]
);

render_layout_start('Kartu Rencana Studi', 'mahasiswa', mhs_menu('krs'));
?>
<div class="glass-card mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <p class="genz-title mb-1">KRS <?= e($ta['tahun_akademik'] ?? '—') ?> — <?= e($ta['semester'] ?? '—') ?></p>
            <p class="small text-muted mb-0">Semester kuliah Anda: <strong><?= $semester ?></strong> · NIM <?= e($nim) ?></p>
        </div>
        <div class="small text-muted">Alur: Pending → Dosen Wali → Admin → Jadwal aktif</div>
    </div>
</div>

<?php if ($noTahunAktif): ?>
<div class="alert-genz danger">Tahun akademik belum aktif — hubungi admin.</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="glass-card">
            <p class="genz-title">Mata Kuliah Tersedia (Semester <?= $semester ?>)</p>
            <?php if (!$mkTersedia): ?>
            <p class="text-muted small">Semua mata kuliah semester <?= $semester ?> sudah diambil, atau admin belum menambahkan mata kuliah aktif untuk semester ini.</p>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($mkTersedia as $mk): ?>
                <div class="col-md-6">
                    <div class="glass-card p-3 h-100" style="background:rgba(255,255,255,.04)">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge-status badge-role"><?= e($mk['kode']) ?></span>
                            <span class="small fw-bold"><?= $mk['sks'] ?> SKS</span>
                        </div>
                        <h6 class="fw-bold mb-1"><?= e($mk['nama']) ?></h6>
                        <p class="small text-muted mb-2">Semester <?= $mk['semester'] ?></p>
                        <p class="small mb-3">
                            <span class="text-muted">Dosen:</span>
                            <?= e($mk['nama_dosen'] ?: 'Belum ditentukan') ?>
                        </p>
                        <form method="post">
                            <input type="hidden" name="action" value="ambil_mk">
                            <input type="hidden" name="kode_mk" value="<?= e($mk['kode']) ?>">
                            <button class="btn-genz btn-genz-sm w-100" <?= $noTahunAktif ? 'disabled' : '' ?>>
                                + Ambil Mata Kuliah
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="glass-card">
            <p class="genz-title">KRS Saya</p>
            <?php if (!$krsList): ?>
            <p class="text-muted small">Belum ada mata kuliah di KRS. Pilih dari daftar di sebelah kiri.</p>
            <?php else: ?>
            <div class="list-shine">
                <?php $totalSks = 0; foreach ($krsList as $k): $totalSks += $k['sks']; ?>
                <div class="list-item d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <strong><?= e($k['nama_mk']) ?></strong>
                        <div class="small text-muted"><?= e($k['kode_mk']) ?> · <?= $k['sks'] ?> SKS</div>
                        <div class="mt-1"><?= status_badge($k['status_krs']) ?></div>
                    </div>
                    <?php if ($k['status_krs'] === 'Pending'): ?>
                    <a href="?hapus=<?= $k['id_krs'] ?>" class="btn-genz btn-genz-danger btn-genz-sm"
                       onclick="return confirm('Hapus mata kuliah dari KRS?')">Hapus</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-3 pt-3 border-top d-flex justify-content-between">
                <strong>Total SKS</strong>
                <strong><?= $totalSks ?></strong>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php render_layout_end(); ?>
