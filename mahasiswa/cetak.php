<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('mahasiswa');

$mhs = db_fetch_one('SELECT * FROM mahasiswa WHERE user_id=?', 'i', [current_user()['id']]);
$nim = $mhs['nim'];
$ta = tahun_akademik_aktif();
$idTahun = $ta['id_tahun'] ?? 1;

$krs = db_fetch_all(
    "SELECT k.*, mk.nama AS nama_mk, mk.sks FROM krs k JOIN mata_kuliah mk ON mk.kode=k.kode_mk WHERE k.nim=? AND k.id_tahun=?",
    'si', [$nim, $idTahun]
);
$nilai = db_fetch_all(
    "SELECT n.*, mk.nama AS nama_mk, mk.sks FROM nilai n JOIN mata_kuliah mk ON mk.kode=n.kode_mk WHERE n.nim=?",
    's', [$nim]
);

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="transkrip_' . $nim . '.xls"');
    echo "NIM\tNama MK\tSKS\tNilai Akhir\tGrade\n";
    foreach ($nilai as $n) {
        echo "{$nim}\t{$n['nama_mk']}\t{$n['sks']}\t{$n['nilai_akhir']}\t{$n['grade']}\n";
    }
    exit;
}

render_layout_start('Cetak Dokumen', 'mahasiswa', mhs_menu('cetak'));
?>
<div class="row g-3">
    <div class="col-md-4">
        <div class="glass-card text-center">
            <p class="genz-title">KRS</p>
            <button onclick="document.getElementById('printKrs').style.display='block';window.print();" class="btn-genz">Cetak KRS</button>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card text-center">
            <p class="genz-title">KHS</p>
            <a href="nilai.php" onclick="window.open('nilai.php');return false;" class="btn-genz">Cetak KHS</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card text-center">
            <p class="genz-title">Transkrip</p>
            <button onclick="window.print()" class="btn-genz mb-2">Cetak PDF</button>
        </div>
    </div>
</div>

<div id="printKrs" class="glass-card mt-4">
    <h5 class="fw-bold text-center mb-3">KARTU RENCANA STUDI</h5>
    <p><?= e($mhs['nama']) ?> · NIM <?= e($nim) ?> · <?= e($ta['tahun_akademik'] ?? '') ?> (<?= e($ta['semester'] ?? '') ?>)</p>
    <table class="table-glass">
        <thead><tr><th>No</th><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Status</th></tr></thead>
        <tbody>
        <?php $no=1; $sks=0; foreach($krs as $k): $sks+=$k['sks']; ?>
        <tr><td><?= $no++ ?></td><td><?= e($k['kode_mk']) ?></td><td><?= e($k['nama_mk']) ?></td><td><?= $k['sks'] ?></td><td><?= e($k['status_krs']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="3"><strong>Total SKS</strong></td><td colspan="2"><strong><?= $sks ?></strong></td></tr></tfoot>
    </table>
</div>

<div id="printTranskrip" class="glass-card mt-4">
    <h5 class="fw-bold text-center mb-3">TRANSKRIP NILAI</h5>
    <p>IPK: <strong><?= hitung_ipk($nim) ?></strong></p>
    <table class="table-glass">
        <thead><tr><th>Mata Kuliah</th><th>SKS</th><th>Nilai</th><th>Grade</th></tr></thead>
        <tbody>
        <?php foreach($nilai as $n): ?>
        <tr><td><?= e($n['nama_mk']) ?></td><td><?= $n['sks'] ?></td><td><?= $n['nilai_akhir'] ?></td><td><?= e($n['grade']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php render_layout_end(); ?>
