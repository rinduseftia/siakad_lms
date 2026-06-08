<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

$rows = db_fetch_all(
    "SELECT p.*, m.nama AS nama_mhs, mk.nama AS nama_mk, j.hari, j.ruangan
     FROM presensi p JOIN mahasiswa m ON m.nim=p.nim JOIN jadwal j ON j.id_jadwal=p.id_jadwal
     JOIN mata_kuliah mk ON mk.kode=j.kode_mk ORDER BY p.tanggal DESC LIMIT 200"
);

render_layout_start('Monitoring Presensi', 'admin', admin_menu('presensi'));
?>
<div class="glass-card p-0 overflow-hidden">
    <div class="table-wrap">
        <table class="table-glass">
            <thead><tr><th>Tanggal</th><th>NIM</th><th>Mahasiswa</th><th>MK</th><th>Pertemuan</th><th>Status</th><th>Ruangan</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['tanggal']) ?></td><td><?= e($r['nim']) ?></td><td><?= e($r['nama_mhs']) ?></td>
                <td><?= e($r['nama_mk']) ?></td><td><?= e($r['pertemuan_ke']) ?></td>
                <td><?= status_badge($r['status_hadir']) ?></td>
                <td><?= e($r['ruangan']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="7" class="text-muted text-center">Belum ada data presensi</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php render_layout_end(); ?>
