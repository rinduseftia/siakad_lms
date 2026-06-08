<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('mahasiswa');

$mhs = db_fetch_one('SELECT nim FROM mahasiswa WHERE user_id=?', 'i', [current_user()['id']]);
$ta = tahun_akademik_aktif();
$idTahun = (int)($ta['id_tahun'] ?? 0);
$rows = db_fetch_all(
    "SELECT j.*, mk.nama AS nama_mk, mk.sks, d.nama AS nama_dosen FROM krs k
     JOIN jadwal j ON j.kode_mk = k.kode_mk AND j.id_tahun = k.id_tahun
     JOIN mata_kuliah mk ON mk.kode = j.kode_mk
     JOIN dosen d ON d.nidn = j.nidn
     WHERE k.nim = ? AND k.status_krs = 'Disetujui Admin'" . ($idTahun ? ' AND k.id_tahun = ?' : '') . "
     ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_mulai",
    $idTahun ? 'si' : 's',
    $idTahun ? [$mhs['nim'], $idTahun] : [$mhs['nim']]
);

render_layout_start('Jadwal Kuliah', 'mahasiswa', mhs_menu('jadwal'));
?>
<div class="glass-card">
<table class="table-glass">
<thead><tr><th>Hari</th><th>Jam</th><th>Mata Kuliah</th><th>SKS</th><th>Dosen</th><th>Ruangan</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
    <td><?= e($r['hari']) ?></td>
    <td><?= substr($r['jam_mulai'],0,5) ?>–<?= substr($r['jam_selesai'],0,5) ?></td>
    <td><?= e($r['nama_mk']) ?></td><td><?= $r['sks'] ?></td>
    <td><?= e($r['nama_dosen']) ?></td><td><?= e($r['ruangan']) ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted">Belum ada jadwal. Pastikan KRS sudah disetujui admin.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?php render_layout_end(); ?>
