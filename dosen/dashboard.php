<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('dosen');

$user = current_user();
$dosen = db_fetch_one('SELECT * FROM dosen WHERE user_id=?', 'i', [$user['id']]);
$nidn = $dosen['nidn'] ?? $_SESSION['nidn'];
$hariIni = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w')];
if ($hariIni === 'Minggu') $hariIni = 'Senin';

$jadwalHari = db_fetch_all(
    "SELECT j.*, mk.nama AS nama_mk FROM jadwal j JOIN mata_kuliah mk ON mk.kode=j.kode_mk
     WHERE j.nidn=? AND j.hari=? ORDER BY j.jam_mulai",
    'ss', [$nidn, $hariIni]
);
$perwalian = db_fetch_all(
    "SELECT nim, nama, semester_sekarang FROM mahasiswa WHERE id_dosen_wali=? ORDER BY nama LIMIT 10",
    's', [$nidn]
);
$krsBaru = (int)(db_fetch_one(
    "SELECT COUNT(*) c FROM krs k JOIN mahasiswa m ON m.nim=k.nim WHERE m.id_dosen_wali=? AND k.status_krs='Pending'",
    's', [$nidn]
)['c'] ?? 0);
$pengumuman = get_pengumuman_for_role('dosen');

render_layout_start('Dashboard Dosen', 'dosen', dosen_menu('dashboard'));
?>
<div class="stat-grid">
    <div class="stat-card"><div class="num"><?= count($jadwalHari) ?></div><div class="lbl">Jadwal Hari Ini</div></div>
    <div class="stat-card"><div class="num"><?= count($perwalian) ?></div><div class="lbl">Mahasiswa Perwalian</div></div>
    <div class="stat-card"><div class="num"><?= $krsBaru ?></div><div class="lbl">KRS Pending</div></div>
</div>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="glass-card">
            <p class="genz-title">Jadwal Mengajar — <?= e($hariIni) ?></p>
            <?php foreach ($jadwalHari as $j): ?>
            <div class="mb-2 pb-2 border-bottom">
                <strong><?= e($j['nama_mk']) ?></strong>
                <div class="small text-muted"><?= substr($j['jam_mulai'],0,5) ?>–<?= substr($j['jam_selesai'],0,5) ?> · <?= e($j['ruangan']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (!$jadwalHari): ?><p class="text-muted small">Tidak ada jadwal hari ini</p><?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card mb-3">
            <p class="genz-title">Mahasiswa Perwalian</p>
            <?php foreach ($perwalian as $m): ?>
            <div class="d-flex justify-content-between mb-2"><span><?= e($m['nama']) ?></span><span class="text-muted small"><?= e($m['nim']) ?> · Sem <?= e($m['semester_sekarang']) ?></span></div>
            <?php endforeach; ?>
            <?php if ($krsBaru): ?><a href="krs.php" class="btn-genz btn-genz-sm mt-2">Lihat <?= $krsBaru ?> KRS baru →</a><?php endif; ?>
        </div>
        <div class="glass-card list-shine">
            <p class="genz-title">Pengumuman</p>
            <?php foreach (array_slice($pengumuman, 0, 5) as $p): ?>
            <div class="list-item">
                <strong class="small"><?= e($p['judul']) ?></strong>
                <p class="small text-muted mb-0"><?= e(mb_substr($p['isi'], 0, 100)) ?></p>
            </div>
            <?php endforeach; ?>
            <?php if (!$pengumuman): ?><p class="text-muted small">Tidak ada pengumuman untuk dosen.</p><?php endif; ?>
        </div>
    </div>
</div>
<?php render_layout_end(); ?>
