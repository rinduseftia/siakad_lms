<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('mahasiswa');

$mhs = db_fetch_one('SELECT * FROM mahasiswa WHERE user_id=?', 'i', [current_user()['id']]);
$nim = $mhs['nim'];
$rows = db_fetch_all(
    "SELECT n.*, mk.nama AS nama_mk, mk.sks, ta.tahun_akademik, ta.semester
     FROM nilai n JOIN mata_kuliah mk ON mk.kode=n.kode_mk
     JOIN tahun_akademik ta ON ta.id_tahun=n.id_tahun WHERE n.nim=? ORDER BY ta.id_tahun, mk.kode",
    's', [$nim]
);

render_layout_start('Nilai & KHS', 'mahasiswa', mhs_menu('nilai'));
?>
<div class="stat-grid mb-3">
    <div class="stat-card"><div class="num"><?= hitung_ipk($nim) ?></div><div class="lbl">IPK Kumulatif</div></div>
</div>
<div class="glass-card" id="khsPrint">
    <div class="d-flex justify-content-between mb-3 no-print">
        <p class="genz-title mb-0">Kartu Hasil Studi</p>
        <button onclick="window.print()" class="btn-genz btn-genz-sm no-print">Cetak KHS</button>
    </div>
    <p><strong><?= e($mhs['nama']) ?></strong> · NIM <?= e($nim) ?> · <?= e($mhs['program_studi']) ?></p>
    <table class="table-glass">
        <thead><tr><th>MK</th><th>SKS</th><th>Tugas</th><th>Quiz</th><th>UTS</th><th>UAS</th><th>Akhir</th><th>Grade</th><th>Semester</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['nama_mk']) ?></td><td><?= $r['sks'] ?></td>
            <td><?= $r['tugas'] ?></td><td><?= $r['quiz'] ?></td><td><?= $r['uts'] ?></td><td><?= $r['uas'] ?></td>
            <td><strong><?= $r['nilai_akhir'] ?></strong></td><td><?= e($r['grade']) ?></td>
            <td><?= e($r['tahun_akademik']) ?> (<?= e($r['semester']) ?>)</td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php render_layout_end(); ?>
