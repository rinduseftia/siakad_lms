<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

$filterSem = (int)($_GET['semester'] ?? 0);
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'bulk_delete') {
        foreach (array_filter(explode(',', $_POST['ids'] ?? '')) as $kode) {
            db_query('DELETE FROM mata_kuliah WHERE kode = ?', 's', [$kode]);
        }
        flash('success', 'Mata kuliah terpilih dihapus.');
    } elseif ($action === 'bulk_status') {
        $status = $_POST['bulk_status'] ?? 'Aktif';
        foreach (array_filter(explode(',', $_POST['ids'] ?? '')) as $kode) {
            db_query('UPDATE mata_kuliah SET status = ? WHERE kode = ?', 'ss', [$status, $kode]);
        }
        flash('success', 'Status mata kuliah diperbarui.');
    }
    
    // Redirect dengan mempertahankan filter
    $queryParams = [];
    if ($filterSem) $queryParams[] = "semester=$filterSem";
    if ($search) $queryParams[] = "search=" . urlencode($search);
    $queryString = !empty($queryParams) ? '?' . implode('&', $queryParams) : '';
    
    redirect('admin/matakuliah.php' . $queryString);
}

if (isset($_GET['hapus'])) {
    db_query('DELETE FROM mata_kuliah WHERE kode = ?', 's', [$_GET['hapus']]);
    flash('success', 'Mata kuliah dihapus.');
    redirect('admin/matakuliah.php');
}

$sql = "SELECT mk.*, GROUP_CONCAT(DISTINCT d.nama ORDER BY d.nama SEPARATOR ', ') AS nama_dosen
        FROM mata_kuliah mk
        LEFT JOIN jadwal j ON j.kode_mk = mk.kode
        LEFT JOIN dosen d ON d.nidn = j.nidn";

$whereClauses = [];
$params = [];
$types = '';

if ($filterSem) {
    $whereClauses[] = 'mk.semester = ?';
    $params[] = $filterSem;
    $types .= 'i';
}

if ($search) {
    $whereClauses[] = '(mk.kode LIKE ? OR mk.nama LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if ($whereClauses) {
    $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
}

$sql .= ' GROUP BY mk.id ORDER BY mk.semester, mk.kode';
$rows = db_fetch_all($sql, $types, $params);
$semesters = db_fetch_all('SELECT DISTINCT semester FROM mata_kuliah ORDER BY semester');

render_layout_start('Mata Kuliah', 'admin', admin_menu('matakuliah'));
?>
<div class="page-toolbar d-flex flex-wrap gap-2 align-items-center">
    <form method="get" class="m-0">
        <?php if (!empty($_GET['search'])): ?>
            <input type="hidden" name="search" value="<?= e($_GET['search']) ?>">
        <?php endif; ?>
        <select name="semester" class="form-select form-select-sm" style="min-width:160px" onchange="this.form.submit()">
            <option value="0">Semua Semester</option>
            <?php foreach ($semesters as $s): ?>
            <option value="<?= $s['semester'] ?>" <?= $filterSem == $s['semester'] ? 'selected' : '' ?>>Semester <?= $s['semester'] ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    
    <button type="button" id="bulkDeleteBtn" class="btn-genz btn-genz-danger btn-genz-sm">Hapus Terpilih</button>
    
    <form method="post" class="d-flex gap-2 align-items-center m-0" data-bulk-sync>
        <input type="hidden" name="action" value="bulk_status">
        <input type="hidden" name="ids" id="bulkIds2">
        <select name="bulk_status" class="form-select form-select-sm" style="min-width:110px">
            <option>Aktif</option><option>Nonaktif</option>
        </select>
        <button type="submit" class="btn-genz btn-genz-outline btn-genz-sm">Ubah Status</button>
    </form>
    
    <span class="toolbar-spacer flex-grow-1"></span>
    
    <form method="get" class="d-flex gap-2 m-0 align-items-center">
        <?php if ($filterSem): ?>
            <input type="hidden" name="semester" value="<?= $filterSem ?>">
        <?php endif; ?>
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari Kode atau Nama..." value="<?= e($_GET['search'] ?? '') ?>" style="min-width:200px">
        <button type="submit" class="btn-genz btn-genz-sm">Cari</button>
        <?php if (!empty($_GET['search']) || $filterSem > 0): ?>
            <a href="matakuliah.php" class="btn-genz btn-genz-outline btn-genz-sm">Reset</a>
        <?php endif; ?>
    </form>

    <a href="matakuliah_form.php" class="btn-genz btn-genz-sm">Tambah Mata Kuliah</a>
</div>

<form id="bulkForm" method="post" class="d-none">
    <input type="hidden" name="action" value="bulk_delete">
    <input type="hidden" name="ids" id="bulkIds">
</form>

<div class="glass-card p-0 overflow-hidden">
    <div class="table-wrap">
        <table class="table-glass" id="dataTable">
            <thead><tr>
                <th style="width:40px"><input type="checkbox" id="selectAll"></th>
                <th>No</th><th>Kode MK</th><th>Nama Mata Kuliah</th><th>SKS</th><th>Semester</th><th>Dosen Pengampu</th><th>Status</th><th>Aksi</th>
            </tr></thead>
            <tbody>
            <?php $no=1; foreach ($rows as $r): ?>
            <tr>
                <td><input type="checkbox" class="row-check" value="<?= e($r['kode']) ?>"></td>
                <td><?= $no++ ?></td>
                <td><strong><?= e($r['kode']) ?></strong></td>
                <td><?= e($r['nama']) ?></td>
                <td><?= e($r['sks']) ?></td>
                <td>Semester <?= e($r['semester']) ?></td>
                <td><?= e($r['nama_dosen'] ?: '—') ?></td>
                <td><?= status_badge($r['status']) ?></td>
                <td class="td-actions">
                    <a href="matakuliah_form.php?kode=<?= urlencode($r['kode']) ?>" class="btn-genz btn-genz-outline btn-genz-sm">Edit</a>
                    <a href="?hapus=<?= urlencode($r['kode']) ?>" class="btn-genz btn-genz-danger btn-genz-sm" onclick="return confirm('Hapus mata kuliah ini?')">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Belum ada data mata kuliah</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php render_layout_end(); ?>