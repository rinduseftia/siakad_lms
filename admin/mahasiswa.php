<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

// Bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_delete') {
    $ids = array_filter(explode(',', $_POST['ids'] ?? ''));
    foreach ($ids as $nim) {
        delete_mahasiswa_cascade($nim);
    }
    audit_log(current_user()['id'], 'Bulk delete mahasiswa: ' . count($ids));
    flash('success', count($ids) . ' mahasiswa dihapus.');
    redirect('admin/mahasiswa.php');
}

// Single delete
if (isset($_GET['hapus'])) {
    $nim = $_GET['hapus'];
    delete_mahasiswa_cascade($nim);
    flash('success', "Mahasiswa $nim dihapus.");
    redirect('admin/mahasiswa.php');
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$total = (int)(db_fetch_one('SELECT COUNT(*) c FROM mahasiswa')['c'] ?? 0);
$pg = pagination($total, $page, $perPage);

$rows = db_fetch_all(
    "SELECT m.*, d.nama AS nama_wali FROM mahasiswa m
     LEFT JOIN dosen d ON d.nidn = m.id_dosen_wali
     ORDER BY m.nim LIMIT ? OFFSET ?",
    'ii', [$perPage, $pg['offset']]
);

render_layout_start('Data Mahasiswa', 'admin', admin_menu('mahasiswa'));
?>
<div class="page-toolbar no-print">
    <button type="button" id="bulkDeleteBtn" class="btn-genz btn-genz-danger btn-genz-sm">Hapus Terpilih</button>
    <span class="toolbar-spacer"></span>
    <a href="mahasiswa_form.php" class="btn-genz btn-genz-sm">Tambah Mahasiswa</a>
</div>
<form id="bulkForm" method="post" class="d-none">
    <input type="hidden" name="action" value="bulk_delete">
    <input type="hidden" name="ids" id="bulkIds">
</form>
<div class="glass-card p-0 overflow-hidden">
    <div class="table-wrap">
        <table class="table-glass" id="dataTable">
            <thead><tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>NO</th><th>NIM</th><th>Nama</th><th>Jurusan</th><th>Prodi</th>
                <th>Angkatan</th><th>Semester</th><th>IPK</th><th>Dosen Wali</th>
                <th>Status</th><th>Email</th><th>Agama</th><th>JK</th>
                <th>Tempat Lahir</th><th>Tgl Lahir</th><th>Alamat</th><th>Aksi</th>
            </tr></thead>
            <tbody>
            <?php $no = $pg['offset'] + 1; foreach ($rows as $r): ?>
            <tr>
                <td><input type="checkbox" class="row-check" value="<?= e($r['nim']) ?>"></td>
                <td><?= $no++ ?></td>
                <td><?= e($r['nim']) ?></td>
                <td><?= e($r['nama']) ?></td>
                <td><?= e($r['jur']) ?></td>
                <td><?= e($r['program_studi']) ?></td>
                <td><?= e($r['angkatan'] ?? '—') ?></td>
                <td><?= e($r['semester_sekarang'] ?? '—') ?></td>
                <td><?= hitung_ipk($r['nim']) ?></td>
                <td><?= e($r['nama_wali'] ?? '—') ?></td>
                <td><?= status_badge($r['status']) ?></td>
                <td><?= e($r['email']) ?></td>
                <td><?= e($r['agama']) ?></td>
                <td><?= e($r['jk']) ?></td>
                <td><?= e($r['tmp_lahir']) ?></td>
                <td><?= e($r['tgl_lahir']) ?></td>
                <td><?= e(mb_substr($r['alamat'], 0, 40)) ?></td>
                <td class="td-actions no-print">
                    <a href="mahasiswa_form.php?nim=<?= urlencode($r['nim']) ?>" class="btn-genz btn-genz-outline btn-genz-sm">Edit</a>
                    <a href="?hapus=<?= urlencode($r['nim']) ?>" class="btn-genz btn-genz-danger btn-genz-sm" onclick="return confirm('Hapus mahasiswa ini?')">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= render_pagination($pg['page'], $pg['totalPages'], 'admin/mahasiswa.php') ?>
</div>
<?php render_layout_end(); ?>
