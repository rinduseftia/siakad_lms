<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_delete') {
    foreach (array_filter(explode(',', $_POST['ids'] ?? '')) as $nidn) {
        delete_dosen_cascade($nidn);
    }
    flash('success', 'Dosen terpilih dihapus.');
    redirect('admin/dosen.php');
}
if (isset($_GET['hapus'])) {
    delete_dosen_cascade($_GET['hapus']);
    flash('success', 'Dosen dihapus.');
    redirect('admin/dosen.php');
}

$search = $_GET['search'] ?? '';
$where = '';
$params = [];
$types = '';

// Jika ada pencarian, buat kondisi WHERE untuk NIDN atau Nama
if ($search) {
    $where = " WHERE nidn LIKE ? OR nama LIKE ?";
    $types = 'ss';
    $params = ["%$search%", "%$search%"];
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

// Hitung total data berdasarkan pencarian
if ($search) {
    $total = (int)(db_fetch_one("SELECT COUNT(*) c FROM dosen $where", $types, $params)['c'] ?? 0);
} else {
    $total = (int)(db_fetch_one("SELECT COUNT(*) c FROM dosen")['c'] ?? 0);
}

$pg = pagination($total, $page, $perPage);

// Tambahkan parameter LIMIT dan OFFSET
$params[] = $perPage;
$params[] = $pg['offset'];
$types .= 'ii';

// Ambil data dosen sesuai pencarian
$rows = db_fetch_all(
    "SELECT * FROM dosen $where ORDER BY nama LIMIT ? OFFSET ?",
    $types,
    $params
);

render_layout_start('Data Dosen', 'admin', admin_menu('dosen'));
?>
<div class="page-toolbar d-flex align-items-center gap-2">
    <button type="button" id="bulkDeleteBtn" class="btn-genz btn-genz-danger btn-genz-sm">Hapus Terpilih</button>

    <span class="toolbar-spacer flex-grow-1"></span>

    <!-- Form Pencarian Dosen -->
    <form method="GET" class="d-flex gap-2 m-0">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari NIDN atau Nama..." value="<?= e($_GET['search'] ?? '') ?>">
        <button type="submit" class="btn-genz btn-genz-sm">Cari</button>
        <?php if (!empty($_GET['search'])): ?>
            <a href="dosen.php" class="btn-genz btn-genz-outline btn-genz-sm">Reset</a>
        <?php endif; ?>
    </form>

    <a href="dosen_form.php" class="btn-genz btn-genz-sm">Tambah Dosen</a>
</div>
<form id="bulkForm" method="post" class="d-none"><input type="hidden" name="action" value="bulk_delete"><input type="hidden" name="ids" id="bulkIds"></form>
<div class="glass-card p-0 overflow-hidden">
    <div class="table-wrap">
        <table class="table-glass" id="dataTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>No</th>
                    <th>Foto</th>
                    <th>NIDN</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Agama</th>
                    <th>Jabatan</th>
                    <th>Status</th>
                    <th>Gender</th>
                    <th>Tempat Lahir</th>
                    <th>Tgl Lahir</th>
                    <th>Alamat</th>
                    <th>No HP</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = $pg['offset'] + 1;
                foreach ($rows as $r): ?>
                    <tr>
                        <td><input type="checkbox" class="row-check" value="<?= e($r['nidn']) ?>"></td>
                        <td><?= $no++ ?></td>
                        <td><?php if ($r['foto']): ?><img src="<?= base_url('uploads/dosen/' . $r['foto']) ?>" class="avatar-sm"><?php else: ?>—<?php endif; ?></td>
                        <td><?= e($r['nidn']) ?></td>
                        <td><?= e($r['nama']) ?></td>
                        <td><?= e($r['email']) ?></td>
                        <td><?= e($r['agama']) ?></td>
                        <td><?= e($r['jabatan'] ?? 'Dosen') ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td><?= e($r['jk']) ?></td>
                        <td><?= e($r['tmp_lahir']) ?></td>
                        <td><?= e($r['tgl_lahir']) ?></td>
                        <td><?= e(mb_substr($r['alamat'] ?? '', 0, 30)) ?></td>
                        <td><?= e($r['no_hp']) ?></td>
                        <td class="td-actions">
                            <a href="dosen_form.php?nidn=<?= urlencode($r['nidn']) ?>" class="btn-genz btn-genz-outline btn-genz-sm">Edit</a>
                            <a href="?hapus=<?= urlencode($r['nidn']) ?>" class="btn-genz btn-genz-danger btn-genz-sm" onclick="return confirm('Hapus dosen ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    $baseUrl = 'admin/dosen.php' . ($search ? '?search=' . urlencode($search) : '');
    ?>
    <?= render_pagination($pg['page'], $pg['totalPages'], $baseUrl) ?>
</div>
<?php render_layout_end(); ?>