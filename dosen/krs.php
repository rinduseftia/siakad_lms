<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('dosen');

$dosen = db_fetch_one('SELECT nidn FROM dosen WHERE user_id=?', 'i', [current_user()['id']]);
$nidn = $dosen['nidn'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id_krs'];
    $aksi = $_POST['aksi'];
    $catatan = trim($_POST['catatan_dosen'] ?? '');
    $krs = db_fetch_one(
        "SELECT k.* FROM krs k JOIN mahasiswa m ON m.nim=k.nim WHERE k.id_krs=? AND m.id_dosen_wali=? AND k.status_krs='Pending'",
        'is', [$id, $nidn]
    );
    if ($krs) {
        $status = $aksi === 'approve' ? 'Disetujui Dosen' : 'Ditolak Dosen';
        db_query('UPDATE krs SET status_krs=?, catatan_dosen=? WHERE id_krs=?', 'ssi', [$status, $catatan, $id]);
        notify_krs_status($krs['nim'], $status, $krs['kode_mk']);
        // Notifikasi admin
        $admins = db_fetch_all("SELECT id FROM users WHERE role='admin'");
        foreach ($admins as $a) {
            create_notification((int)$a['id'], "KRS {$krs['nim']} — {$krs['kode_mk']}: $status (menunggu verifikasi admin)");
        }
        flash('success', "KRS $status.");
    }
    redirect('dosen/krs.php');
}

$rows = db_fetch_all(
    "SELECT k.*, m.nama AS nama_mhs, mk.nama AS nama_mk FROM krs k
     JOIN mahasiswa m ON m.nim=k.nim JOIN mata_kuliah mk ON mk.kode=k.kode_mk
     WHERE m.id_dosen_wali=? ORDER BY FIELD(k.status_krs,'Pending','Disetujui Dosen','Ditolak Dosen'), k.id_krs DESC",
    's', [$nidn]
);

render_layout_start('Persetujuan KRS', 'dosen', dosen_menu('krs'));
?>
<div class="glass-card">
    <table class="table-glass">
        <thead><tr><th>NIM</th><th>Mahasiswa</th><th>MK</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['nim']) ?></td><td><?= e($r['nama_mhs']) ?></td>
            <td><?= e($r['kode_mk']) ?> — <?= e($r['nama_mk']) ?></td>
            <td><?= status_badge($r['status_krs']) ?></td>
            <td><?= e($r['catatan_dosen'] ?? '—') ?></td>
            <td>
                <?php if ($r['status_krs'] === 'Pending'): ?>
                <form method="post" class="d-flex gap-1 flex-wrap">
                    <input type="hidden" name="id_krs" value="<?= $r['id_krs'] ?>">
                    <input type="text" name="catatan_dosen" class="form-control form-control-sm" placeholder="Catatan" style="width:120px">
                    <button name="aksi" value="approve" class="btn-genz btn-genz-success btn-genz-sm">Setujui</button>
                    <button name="aksi" value="reject" class="btn-genz btn-genz-danger btn-genz-sm">Tolak</button>
                </form>
                <?php else: ?>—<?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php render_layout_end(); ?>
