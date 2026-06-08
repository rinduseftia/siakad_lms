<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

$search = trim($_GET['q'] ?? '');
$idTahun = (int)($_GET['tahun'] ?? 0);

$sql = "SELECT n.*, m.nama AS nama_mhs, mk.nama AS nama_mk, mk.kode AS kode_mk, ta.tahun_akademik
        FROM nilai n
        JOIN mahasiswa m ON m.nim=n.nim
        JOIN mata_kuliah mk ON mk.kode=n.kode_mk
        JOIN tahun_akademik ta ON ta.id_tahun=n.id_tahun
        WHERE 1=1";
$params = [];
$types = '';

if ($idTahun > 0) {
    $sql .= ' AND n.id_tahun = ?';
    $params[] = $idTahun;
    $types .= 'i';
}
if ($search !== '') {
    $sql .= ' AND (m.nim LIKE ? OR m.nama LIKE ? OR mk.nama LIKE ? OR mk.kode LIKE ?)';
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= 'ssss';
}

$sql .= ' ORDER BY m.nim, mk.kode';
$rows = db_fetch_all($sql, $types, $params);
$tahunList = db_fetch_all('SELECT id_tahun, tahun_akademik, semester FROM tahun_akademik ORDER BY id_tahun DESC');

render_layout_start('Monitoring Nilai', 'admin', admin_menu('nilai'));
?>
<div class="page-toolbar mb-3">
    <form method="get" class="d-flex gap-2 flex-wrap">
        <input type="search" name="q" class="form-control" style="min-width:200px" placeholder="Cari NIM, nama, MK..." value="<?= e($search) ?>">
        <select name="tahun" class="form-select" style="min-width:180px">
            <option value="0">Semua Tahun</option>
            <?php foreach ($tahunList as $t): ?>
            <option value="<?= $t['id_tahun'] ?>" <?= $idTahun === (int)$t['id_tahun'] ? 'selected' : '' ?>>
                <?= e($t['tahun_akademik']) ?> (<?= e($t['semester']) ?>)
            </option>
            <?php endforeach; ?>
        </select>
        <button class="btn-genz btn-genz-outline btn-genz-sm">Filter</button>
    </form>
    <span class="toolbar-spacer"></span>
    <span class="small text-muted"><?= count($rows) ?> record</span>
</div>
<div class="glass-card p-0 overflow-hidden">
    <div class="table-wrap">
        <table class="table-glass">
            <thead><tr><th>NIM</th><th>Nama</th><th>MK</th><th>Tahun</th><th>Tugas</th><th>Quiz</th><th>UTS</th><th>UAS</th><th>Akhir</th><th>Grade</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['nim']) ?></td><td><?= e($r['nama_mhs']) ?></td>
                <td><?= e($r['kode_mk']) ?> — <?= e($r['nama_mk']) ?></td><td><?= e($r['tahun_akademik']) ?></td>
                <td><?= e($r['tugas']) ?></td><td><?= e($r['quiz']) ?></td>
                <td><?= e($r['uts']) ?></td><td><?= e($r['uas']) ?></td>
                <td><strong><?= e($r['nilai_akhir']) ?></strong></td>
                <td><?= status_badge($r['grade']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">
                Belum ada data nilai. Nilai akan muncul setelah dosen menginput nilai mahasiswa yang KRS-nya sudah disetujui admin.
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php render_layout_end(); ?>
