<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id_krs'];
    $aksi = $_POST['aksi'];
    $krs = db_fetch_one(
        'SELECT k.*, mk.nama AS nama_mk FROM krs k JOIN mata_kuliah mk ON mk.kode=k.kode_mk WHERE k.id_krs=?',
        'i',
        [$id]
    );
    $canAct = $krs && in_array($krs['status_krs'], ['Pending', 'Disetujui Dosen'], true);

    if ($canAct) {
        if ($aksi === 'approve') {
            $newStatus = 'Disetujui Admin';
            db_query('UPDATE krs SET status_krs=? WHERE id_krs=?', 'si', [$newStatus, $id]);

            $jadwal = db_fetch_one(
                'SELECT nidn FROM jadwal WHERE kode_mk=? AND id_tahun=? LIMIT 1',
                'si',
                [$krs['kode_mk'], $krs['id_tahun']]
            );
            if (!$jadwal) {
                $jadwal = db_fetch_one('SELECT nidn FROM jadwal WHERE kode_mk=? LIMIT 1', 's', [$krs['kode_mk']]);
            }
            if ($jadwal && $jadwal['nidn']) {
                ensure_jadwal_mk($krs['kode_mk'], $jadwal['nidn'], (int)$krs['id_tahun']);
            }

            notify_krs_status($krs['nim'], $newStatus, $krs['kode_mk']);
            audit_log(current_user()['id'], "Admin setujui KRS #{$id}");
            flash('success', 'KRS disetujui. Jadwal kuliah otomatis dibuat jika dosen pengampu tersedia.');
        } elseif ($aksi === 'reject') {
            $newStatus = $krs['status_krs'] === 'Pending' ? 'Ditolak Admin' : 'Ditolak Admin';
            db_query('UPDATE krs SET status_krs=? WHERE id_krs=?', 'si', [$newStatus, $id]);
            notify_krs_status($krs['nim'], $newStatus, $krs['kode_mk']);
            audit_log(current_user()['id'], "Admin tolak KRS #{$id}");
            flash('success', 'KRS ditolak.');
        }
    } else {
        flash('danger', 'KRS tidak dapat diproses pada status saat ini.');
    }
    redirect('admin/krs.php');
}

$filter = $_GET['status'] ?? '';
$sql = "SELECT k.*, m.nama AS nama_mhs, mk.nama AS nama_mk, ta.tahun_akademik, ta.semester
        FROM krs k
        JOIN mahasiswa m ON m.nim=k.nim
        JOIN mata_kuliah mk ON mk.kode=k.kode_mk
        JOIN tahun_akademik ta ON ta.id_tahun=k.id_tahun
        WHERE 1=1";
$params = [];
$types = '';

if ($filter !== '') {
    $sql .= ' AND k.status_krs = ?';
    $params[] = $filter;
    $types .= 's';
}

$sql .= " ORDER BY FIELD(k.status_krs,'Pending','Disetujui Dosen','Disetujui Admin','Ditolak Dosen','Ditolak Admin'), k.id_krs DESC";
$rows = db_fetch_all($sql, $types, $params);

$stats = [
    'pending' => (int)(db_fetch_one("SELECT COUNT(*) c FROM krs WHERE status_krs='Pending'")['c'] ?? 0),
    'dosen_ok' => (int)(db_fetch_one("SELECT COUNT(*) c FROM krs WHERE status_krs='Disetujui Dosen'")['c'] ?? 0),
];

render_layout_start('Persetujuan KRS', 'admin', admin_menu('krs'));
?>
<div class="stat-grid mb-3" style="grid-template-columns:repeat(2,1fr)">
    <div class="stat-card"><div class="num"><?= $stats['pending'] ?></div><div class="lbl">Menunggu Persetujuan</div></div>
    <div class="stat-card"><div class="num"><?= $stats['dosen_ok'] ?></div><div class="lbl">Disetujui Dosen</div></div>
</div>
<div class="page-toolbar mb-3">
    <form method="get" class="d-flex gap-2">
        <select name="status" class="form-select form-select-sm" style="min-width:200px" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <?php foreach (['Pending','Disetujui Dosen','Disetujui Admin','Ditolak Dosen','Ditolak Admin'] as $s): ?>
            <option value="<?= $s ?>" <?= $filter === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
<div class="glass-card p-0 overflow-hidden">
    <div class="table-wrap">
        <table class="table-glass">
            <thead><tr><th>NIM</th><th>Mahasiswa</th><th>MK</th><th>Tahun</th><th>Status</th><th>Catatan Dosen</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['nim']) ?></td><td><?= e($r['nama_mhs']) ?></td>
                <td><?= e($r['kode_mk']) ?> — <?= e($r['nama_mk']) ?></td>
                <td><?= e($r['tahun_akademik']) ?> (<?= e($r['semester']) ?>)</td>
                <td><?= status_badge($r['status_krs']) ?></td>
                <td><?= e($r['catatan_dosen'] ?? '—') ?></td>
                <td class="td-actions">
                    <?php if (in_array($r['status_krs'], ['Pending', 'Disetujui Dosen'], true)): ?>
                    <form method="post" class="d-inline-flex gap-1">
                        <input type="hidden" name="id_krs" value="<?= $r['id_krs'] ?>">
                        <button name="aksi" value="approve" class="btn-genz btn-genz-success btn-genz-sm">Setujui</button>
                        <button name="aksi" value="reject" class="btn-genz btn-genz-danger btn-genz-sm">Tolak</button>
                    </form>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pengajuan KRS</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php render_layout_end(); ?>
