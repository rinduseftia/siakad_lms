<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('mahasiswa');

$user = current_user();
$mhs = db_fetch_one('SELECT * FROM mahasiswa WHERE user_id=?', 'i', [$user['id']]);
$nim = $mhs['nim'] ?? $user['username'];
$ipk = hitung_ipk($nim);
$ta = tahun_akademik_aktif();
$ips = $ta ? hitung_ips($nim, (int)$ta['id_tahun']) : 0;
$pengumuman = get_pengumuman_for_role('mahasiswa');

$nilaiChart = db_fetch_all(
    "SELECT mk.nama, n.nilai_akhir FROM nilai n JOIN mata_kuliah mk ON mk.kode=n.kode_mk WHERE n.nim=? ORDER BY n.id_nilai",
    's', [$nim]
);
$timeline = db_fetch_all(
    "SELECT j.hari, j.jam_mulai, mk.nama AS nama_mk, j.ruangan FROM krs k
     JOIN jadwal j ON j.kode_mk = k.kode_mk AND j.id_tahun = k.id_tahun
     JOIN mata_kuliah mk ON mk.kode = k.kode_mk
     WHERE k.nim = ? AND k.status_krs = 'Disetujui Admin'" . ($ta ? ' AND k.id_tahun = ?' : '') . "
     ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu')",
    $ta ? 'si' : 's',
    $ta ? [$nim, (int)$ta['id_tahun']] : [$nim]
);

render_layout_start('Dashboard Mahasiswa', 'mahasiswa', mhs_menu('dashboard'));
?>
<div class="stat-grid">
    <div class="stat-card"><div class="num"><?= $ipk ?></div><div class="lbl">IPK</div></div>
    <div class="stat-card"><div class="num"><?= $ips ?></div><div class="lbl">IPS Semester Ini</div></div>
    <div class="stat-card"><div class="num"><?= e($mhs['semester_sekarang'] ?? '—') ?></div><div class="lbl">Semester</div></div>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="glass-card">
            <p class="genz-title">Grafik Perkembangan Nilai</p>
            <canvas id="chartNilai" height="120"></canvas>
        </div>
        <div class="glass-card mt-3 list-shine">
            <p class="genz-title">Timeline Perkuliahan</p>
            <?php foreach ($timeline as $t): ?>
            <div class="list-item">
                <strong><?= e($t['nama_mk']) ?></strong>
                <div class="small text-muted"><?= e($t['hari']) ?> · <?= substr($t['jam_mulai'],0,5) ?> · <?= e($t['ruangan']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="glass-card list-shine">
            <p class="genz-title">Pengumuman Terbaru</p>
            <?php foreach (array_slice($pengumuman, 0, 5) as $p): ?>
            <div class="list-item">
                <strong class="small"><?= e($p['judul']) ?></strong>
                <p class="small text-muted mb-0"><?= e(mb_substr($p['isi'], 0, 100)) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script>
new Chart(document.getElementById('chartNilai'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($nilaiChart, 'nama')) ?>,
    datasets: [{ label: 'Nilai Akhir', data: <?= json_encode(array_map('floatval', array_column($nilaiChart, 'nilai_akhir'))) ?>,
      borderColor: '#075985', backgroundColor: 'rgba(7,89,133,.12)', fill: true, tension: .4 }]
  },
  options: { scales: { y: { min: 0, max: 100 } }, plugins: { legend: { display: false } } }
});
</script>
<?php render_layout_end(); ?>
