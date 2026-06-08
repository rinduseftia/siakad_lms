<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('dosen');

$dosen = db_fetch_one('SELECT nidn FROM dosen WHERE user_id=?', 'i', [current_user()['id']]);
$nidn = $dosen['nidn'] ?? '';
$ta = tahun_akademik_aktif();
$idTahun = $ta['id_tahun'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tugas = (float)$_POST['tugas'];
    $quiz  = (float)$_POST['quiz'];
    $uts   = (float)$_POST['uts'];
    $uas   = (float)$_POST['uas'];
    $akhir = hitung_nilai_akhir($tugas, $quiz, $uts, $uas);
    $grade = konversi_grade($akhir);
    $nim = $_POST['nim'];
    $kode = $_POST['kode_mk'];

    $exists = db_fetch_one('SELECT id_nilai FROM nilai WHERE nim=? AND kode_mk=? AND id_tahun=?', 'ssi', [$nim, $kode, $idTahun]);
    if ($exists) {
        db_query('UPDATE nilai SET tugas=?,quiz=?,uts=?,uas=?,nilai_akhir=?,grade=? WHERE id_nilai=?',
            'dddddsi', [$tugas,$quiz,$uts,$uas,$akhir,$grade,$exists['id_nilai']]);
    } else {
        db_query('INSERT INTO nilai (nim,kode_mk,id_tahun,tugas,quiz,uts,uas,nilai_akhir,grade) VALUES (?,?,?,?,?,?,?,?,?)',
            'sssidddds', [$nim, $kode, $idTahun, $tugas, $quiz, $uts, $uas, $akhir, $grade]);
    }
    flash('success', "Nilai disimpan. Akhir: $akhir ($grade)");
    redirect('dosen/nilai.php');
}

$jadwal = db_fetch_all(
    "SELECT j.kode_mk, mk.nama AS nama_mk FROM jadwal j
     JOIN mata_kuliah mk ON mk.kode = j.kode_mk
     WHERE j.nidn = ?" . ($idTahun ? ' AND j.id_tahun = ?' : '') . "
     GROUP BY j.kode_mk, mk.nama",
    $idTahun ? 'si' : 's',
    $idTahun ? [$nidn, $idTahun] : [$nidn]
);
$selectedMk = $_GET['mk'] ?? ($jadwal[0]['kode_mk'] ?? '');
$mhsList = [];
if ($selectedMk) {
    $mhsList = db_fetch_all(
        "SELECT m.nim, m.nama, n.tugas, n.quiz, n.uts, n.uas, n.nilai_akhir, n.grade
         FROM krs k JOIN mahasiswa m ON m.nim=k.nim
         LEFT JOIN nilai n ON n.nim=m.nim AND n.kode_mk=k.kode_mk AND n.id_tahun=k.id_tahun
         WHERE k.kode_mk=? AND k.status_krs='Disetujui Admin' AND k.id_tahun=?",
        'si', [$selectedMk, $idTahun]
    );
}

render_layout_start('Input Nilai', 'dosen', dosen_menu('nilai'));
?>
<div class="glass-card mb-3">
    <form method="get" class="d-flex gap-2 align-items-end">
        <div><label class="small fw-bold">Mata Kuliah</label>
        <select name="mk" class="form-select" onchange="this.form.submit()">
            <?php foreach ($jadwal as $j): ?>
            <option value="<?= e($j['kode_mk']) ?>" <?= $selectedMk === $j['kode_mk'] ? 'selected' : '' ?>><?= e($j['nama_mk']) ?></option>
            <?php endforeach; ?>
        </select></div>
    </form>
</div>
<?php foreach ($mhsList as $m): ?>
<div class="glass-card mb-3 form-genz">
    <h6 class="fw-bold"><?= e($m['nama']) ?> <span class="text-muted">(<?= e($m['nim']) ?>)</span></h6>
    <form method="post" class="form-nilai">
        <input type="hidden" name="nim" value="<?= e($m['nim']) ?>">
        <input type="hidden" name="kode_mk" value="<?= e($selectedMk) ?>">
        <input type="hidden" name="nilai_akhir" value="<?= e($m['nilai_akhir'] ?? 0) ?>">
        <input type="hidden" name="grade" value="<?= e($m['grade'] ?? 'E') ?>">
        <div class="row g-2">
            <div class="col-md-2"><label>Tugas (20%)</label><input name="tugas" type="number" min="0" max="100" step="0.01" class="form-control" value="<?= e($m['tugas'] ?? 0) ?>"></div>
            <div class="col-md-2"><label>Quiz (20%)</label><input name="quiz" type="number" min="0" max="100" step="0.01" class="form-control" value="<?= e($m['quiz'] ?? 0) ?>"></div>
            <div class="col-md-2"><label>UTS (30%)</label><input name="uts" type="number" min="0" max="100" step="0.01" class="form-control" value="<?= e($m['uts'] ?? 0) ?>"></div>
            <div class="col-md-2"><label>UAS (30%)</label><input name="uas" type="number" min="0" max="100" step="0.01" class="form-control" value="<?= e($m['uas'] ?? 0) ?>"></div>
            <div class="col-md-2"><label>Nilai Akhir</label><div class="form-control bg-transparent nilai-akhir-preview"><?= e($m['nilai_akhir'] ?? 0) ?></div></div>
            <div class="col-md-2"><label>Grade</label><div class="form-control bg-transparent fw-bold grade-preview"><?= e($m['grade'] ?? 'E') ?></div></div>
        </div>
        <button class="btn-genz btn-genz-sm mt-2">Simpan Nilai</button>
    </form>
</div>
<?php endforeach; ?>
<?php if (!$mhsList && $selectedMk): ?><div class="glass-card text-muted">Belum ada mahasiswa dengan KRS disetujui admin untuk MK ini.</div><?php endif; ?>
<?php render_layout_end(); ?>
