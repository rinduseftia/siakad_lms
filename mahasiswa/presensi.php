<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('mahasiswa');

$mhs = db_fetch_one('SELECT nim FROM mahasiswa WHERE user_id=?', 'i', [current_user()['id']]);
$rows = db_fetch_all(
    "SELECT p.*, mk.nama AS nama_mk, j.hari FROM presensi p
     JOIN jadwal j ON j.id_jadwal=p.id_jadwal JOIN mata_kuliah mk ON mk.kode=j.kode_mk
     WHERE p.nim=? ORDER BY p.tanggal DESC",
    's', [$mhs['nim']]
);

render_layout_start('Riwayat Presensi', 'mahasiswa', mhs_menu('presensi'));
?>
<div class="glass-card">
<table class="table-glass">
<thead><tr><th>Tanggal</th><th>Mata Kuliah</th><th>Pertemuan</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
    <td><?= e($r['tanggal']) ?></td><td><?= e($r['nama_mk']) ?></td>
    <td><?= $r['pertemuan_ke'] ?></td>
    <td><?= status_badge($r['status_hadir']) ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="4" class="text-muted text-center">Belum ada riwayat presensi</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?php render_layout_end(); ?>
