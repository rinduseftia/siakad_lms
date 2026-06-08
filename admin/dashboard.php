<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

$stats = [
    'mahasiswa' => db_fetch_one("SELECT COUNT(*) c FROM mahasiswa")['c'] ?? 0,
    'dosen'     => db_fetch_one("SELECT COUNT(*) c FROM dosen")['c'] ?? 0,
    'mk'        => db_fetch_one("SELECT COUNT(*) c FROM mata_kuliah WHERE status = 'Aktif'")['c'] ?? 0,
    'krs_pending' => db_fetch_one("SELECT COUNT(*) c FROM krs WHERE status_krs = 'Disetujui Dosen'")['c'] ?? 0,
];
$aktivitas = db_fetch_all(
    "SELECT a.aktivitas, a.waktu, u.username FROM audit_log a
     LEFT JOIN users u ON u.id = a.user_id ORDER BY a.waktu DESC LIMIT 8"
);
$pengumuman = get_pengumuman_for_role('admin');

render_layout_start('Dashboard Admin', 'admin', admin_menu('dashboard'));
?>
<div class="stat-grid">
    <div class="stat-card"><div class="num"><?= $stats['mahasiswa'] ?></div><div class="lbl">Mahasiswa</div></div>
    <div class="stat-card"><div class="num"><?= $stats['dosen'] ?></div><div class="lbl">Dosen</div></div>
    <div class="stat-card"><div class="num"><?= $stats['mk'] ?></div><div class="lbl">Mata Kuliah Aktif</div></div>
    <div class="stat-card"><div class="num"><?= $stats['krs_pending'] ?></div><div class="lbl">KRS Menunggu Admin</div></div>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="glass-card">
            <p class="genz-title">Grafik Keaktifan</p>
            <canvas id="chartAktivitas" height="120"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card mb-3">
            <p class="genz-title">Kalender Akademik</p>
            <?php $ta = tahun_akademik_aktif(); ?>
            <p class="fw-bold mb-1"><?= e($ta['tahun_akademik'] ?? '—') ?></p>
            <p class="text-muted small">Semester <?= e($ta['semester'] ?? '—') ?> · <?= e($ta['status'] ?? '') ?></p>
        </div>
        <div class="glass-card">
            <p class="genz-title">Pengumuman</p>
            <?php foreach (array_slice($pengumuman, 0, 3) as $p): ?>
            <div class="mb-2 pb-2 border-bottom">
                <strong class="small"><?= e($p['judul']) ?></strong>
                <p class="small text-muted mb-0"><?= e(mb_substr($p['isi'], 0, 80)) ?>...</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<div class="glass-card mt-3">
    <p class="genz-title px-3 pt-3">Audit Log Terbaru</p>
    <div class="table-wrap">
        <table class="table-glass"><thead><tr><th>Waktu</th><th>User</th><th>Aktivitas</th></tr></thead><tbody>
        <?php foreach ($aktivitas as $a): ?>
        <tr><td><?= e($a['waktu']) ?></td><td><?= e($a['username'] ?? '—') ?></td><td><?= e($a['aktivitas']) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
</div>
<script>
const ctx = document.getElementById('chartAktivitas');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: ['Mahasiswa','Dosen','MK Aktif','KRS Pending'],
    datasets: [{ label: 'Statistik', data: [<?= implode(',', array_values($stats)) ?>],
      backgroundColor: ['rgba(7,89,133,.85)','rgba(3,105,161,.7)','rgba(14,165,233,.55)','rgba(2,132,199,.65)'],
      borderRadius: 8 }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
<?php render_layout_end(); ?>
