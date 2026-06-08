<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('dosen');

$dosen = db_fetch_one('SELECT nidn FROM dosen WHERE user_id=?', 'i', [current_user()['id']]);
$nidn = $dosen['nidn'] ?? '';
$ta = tahun_akademik_aktif();
$idTahun = (int)($ta['id_tahun'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idJadwal = (int)($_POST['id_jadwal'] ?? 0);
    $pertemuan = (int)($_POST['pertemuan_ke'] ?? 1);

    $jadwalCheck = db_fetch_one(
        'SELECT id_jadwal FROM jadwal WHERE id_jadwal = ? AND nidn = ?',
        'is',
        [$idJadwal, $nidn]
    );

    if (!$jadwalCheck) {
        flash('danger', 'Jadwal tidak valid atau bukan milik Anda.');
        redirect('dosen/presensi.php');
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'buka_sesi') {
        db_query(
            'INSERT INTO sesi_presensi (id_jadwal, pertemuan_ke, status, tanggal) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE status = ?',
            'iisss',
            [$idJadwal, $pertemuan, 'Buka', date('Y-m-d'), 'Buka']
        );
        flash('success', 'Sesi absensi dibuka.');
    } elseif ($action === 'tutup_sesi') {
        db_query(
            'UPDATE sesi_presensi SET status = ? WHERE id_jadwal = ? AND pertemuan_ke = ?',
            'sii',
            ['Tutup', $idJadwal, $pertemuan]
        );
        flash('success', 'Sesi absensi ditutup.');
    } elseif ($action === 'simpan_presensi') {
        foreach ($_POST['status'] ?? [] as $nim => $status) {
            $exists = db_fetch_one(
                'SELECT id_presensi FROM presensi WHERE id_jadwal = ? AND nim = ? AND pertemuan_ke = ?',
                'isi',
                [$idJadwal, $nim, $pertemuan]
            );
            if ($exists) {
                db_query('UPDATE presensi SET status_hadir = ? WHERE id_presensi = ?', 'si', [$status, $exists['id_presensi']]);
            } else {
                db_query(
                    'INSERT INTO presensi (id_jadwal, nim, pertemuan_ke, status_hadir, tanggal) VALUES (?,?,?,?,?)',
                    'isiss',
                    [$idJadwal, $nim, $pertemuan, $status, date('Y-m-d')]
                );
            }
        }
        flash('success', 'Presensi disimpan.');
    }

    redirect('dosen/presensi.php?id_jadwal=' . $idJadwal . '&pertemuan=' . $pertemuan);
}

$jadwalList = db_fetch_all(
    "SELECT j.*, mk.nama AS nama_mk FROM jadwal j
     JOIN mata_kuliah mk ON mk.kode = j.kode_mk
     WHERE j.nidn = ?" . ($idTahun ? ' AND j.id_tahun = ?' : '') . "
     ORDER BY mk.nama",
    $idTahun ? 'si' : 's',
    $idTahun ? [$nidn, $idTahun] : [$nidn]
);

$idJadwal = (int)($_GET['id_jadwal'] ?? ($jadwalList[0]['id_jadwal'] ?? 0));
$pertemuan = max(1, (int)($_GET['pertemuan'] ?? 1));
$jadwal = $idJadwal ? db_fetch_one(
    'SELECT j.*, mk.nama AS nama_mk FROM jadwal j JOIN mata_kuliah mk ON mk.kode = j.kode_mk WHERE j.id_jadwal = ? AND j.nidn = ?',
    'is',
    [$idJadwal, $nidn]
) : null;
$sesi = $idJadwal ? db_fetch_one(
    'SELECT * FROM sesi_presensi WHERE id_jadwal = ? AND pertemuan_ke = ?',
    'ii',
    [$idJadwal, $pertemuan]
) : null;
$mhsList = [];

if ($jadwal) {
    $mhsList = db_fetch_all(
        "SELECT m.nim, m.nama, p.status_hadir FROM krs k
         JOIN mahasiswa m ON m.nim = k.nim
         LEFT JOIN presensi p ON p.nim = m.nim AND p.id_jadwal = ? AND p.pertemuan_ke = ?
         WHERE k.kode_mk = ? AND k.id_tahun = ? AND k.status_krs = 'Disetujui Admin'
         ORDER BY m.nim",
        'iisi',
        [$idJadwal, $pertemuan, $jadwal['kode_mk'], (int)$jadwal['id_tahun']]
    );
}

render_layout_start('Presensi', 'dosen', dosen_menu('presensi'));
?>
<?php if (!$jadwalList): ?>
<div class="glass-card text-muted">
    <p class="mb-1 fw-bold">Belum ada jadwal kuliah untuk Anda.</p>
    <p class="small mb-0">Pastikan admin sudah menetapkan dosen pengampu pada mata kuliah (Form Mata Kuliah) atau menambahkan jadwal di menu Jadwal Kuliah.</p>
</div>
<?php else: ?>
<div class="glass-card mb-3 form-genz">
<form method="get" class="row g-2">
    <div class="col-md-6"><label>Mata Kuliah / Jadwal</label>
    <select name="id_jadwal" class="form-select" onchange="this.form.submit()">
        <?php foreach ($jadwalList as $j): ?>
        <option value="<?= $j['id_jadwal'] ?>" <?= $idJadwal == $j['id_jadwal'] ? 'selected' : '' ?>><?= e($j['nama_mk']) ?> — <?= e($j['hari']) ?></option>
        <?php endforeach; ?>
    </select></div>
    <div class="col-md-3"><label>Pertemuan Ke</label><input type="number" name="pertemuan" min="1" value="<?= $pertemuan ?>" class="form-control" onchange="this.form.submit()"></div>
</form>
</div>
<?php if ($jadwal): ?>
<div class="d-flex gap-2 mb-3 flex-wrap">
    <form method="post"><input type="hidden" name="action" value="buka_sesi"><input type="hidden" name="id_jadwal" value="<?= $idJadwal ?>"><input type="hidden" name="pertemuan_ke" value="<?= $pertemuan ?>"><button class="btn-genz btn-genz-success btn-genz-sm">Buka Sesi</button></form>
    <form method="post"><input type="hidden" name="action" value="tutup_sesi"><input type="hidden" name="id_jadwal" value="<?= $idJadwal ?>"><input type="hidden" name="pertemuan_ke" value="<?= $pertemuan ?>"><button class="btn-genz btn-genz-outline btn-genz-sm">Tutup Sesi</button></form>
    <span class="align-self-center small">Status: <strong><?= e($sesi['status'] ?? 'Tutup') ?></strong></span>
</div>
<div class="glass-card">
<form method="post">
    <input type="hidden" name="action" value="simpan_presensi">
    <input type="hidden" name="id_jadwal" value="<?= $idJadwal ?>">
    <input type="hidden" name="pertemuan_ke" value="<?= $pertemuan ?>">
    <?php if (!$mhsList): ?>
    <p class="text-muted mb-0">Belum ada mahasiswa. KRS harus disetujui admin terlebih dahulu untuk MK ini.</p>
    <?php else: ?>
    <table class="table-glass">
        <thead><tr><th>NIM</th><th>Nama</th><th>Status Hadir</th></tr></thead>
        <tbody>
        <?php foreach ($mhsList as $m): ?>
        <tr>
            <td><?= e($m['nim']) ?></td><td><?= e($m['nama']) ?></td>
            <td><select name="status[<?= e($m['nim']) ?>]" class="form-select form-select-sm">
                <?php foreach (['Hadir','Izin','Sakit','Alpha'] as $st): ?>
                <option <?= ($m['status_hadir'] ?? 'Alpha') === $st ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
            </select></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <button class="btn-genz mt-2">Simpan Presensi</button>
    <?php endif; ?>
</form>
</div>
<?php endif; ?>
<?php endif; ?>
<?php render_layout_end(); ?>
