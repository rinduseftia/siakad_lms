<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  db_query(
    'INSERT INTO jadwal (kode_mk,nidn,id_tahun,hari,jam_mulai,jam_selesai,ruangan) VALUES (?,?,?,?,?,?,?)',
    'ssissss',
    [$_POST['kode_mk'],$_POST['nidn'],(int)$_POST['id_tahun'],$_POST['hari'],$_POST['jam_mulai'],$_POST['jam_selesai'],$_POST['ruangan']]
  );
  flash('success', 'Jadwal ditambahkan.');
  redirect('admin/jadwal.php');
}
if (isset($_GET['hapus'])) {
  db_query('DELETE FROM jadwal WHERE id_jadwal=?', 'i', [(int)$_GET['hapus']]);
  flash('success', 'Jadwal dihapus.');
  redirect('admin/jadwal.php');
}

$rows = db_fetch_all(
  "SELECT j.*, mk.nama AS nama_mk, d.nama AS nama_dosen, ta.tahun_akademik FROM jadwal j
   JOIN mata_kuliah mk ON mk.kode=j.kode_mk JOIN dosen d ON d.nidn=j.nidn
   JOIN tahun_akademik ta ON ta.id_tahun=j.id_tahun ORDER BY j.hari, j.jam_mulai"
);
$mkList = db_fetch_all("SELECT kode, nama FROM mata_kuliah WHERE status = 'Aktif' ORDER BY kode");
$dosenList = db_fetch_all("SELECT nidn, nama FROM dosen ORDER BY nama");
$tahunList = db_fetch_all('SELECT * FROM tahun_akademik ORDER BY id_tahun DESC');

render_layout_start('Jadwal Kuliah', 'admin', admin_menu('jadwal'));
?>
<div class="row g-3">
<div class="col-lg-4">
<div class="glass-card form-genz">
<p class="genz-title">Buat Jadwal</p>
<form method="post" class="row g-2">
  <div class="col-12"><label>Mata Kuliah</label><select name="kode_mk" class="form-select" required><?php foreach($mkList as $m): ?><option value="<?=e($m['kode'])?>"><?=e($m['kode'].' — '.$m['nama'])?></option><?php endforeach; ?></select></div>
  <div class="col-12"><label>Dosen</label><select name="nidn" class="form-select" required><?php foreach($dosenList as $d): ?><option value="<?=e($d['nidn'])?>"><?=e($d['nama'])?></option><?php endforeach; ?></select></div>
  <div class="col-12"><label>Tahun Akademik</label><select name="id_tahun" class="form-select"><?php foreach($tahunList as $t): ?><option value="<?=$t['id_tahun']?>" <?= ($t['status'] ?? '') === 'Aktif' ? 'selected' : '' ?>><?=e($t['tahun_akademik'].' '.$t['semester'])?><?= ($t['status'] ?? '') === 'Aktif' ? ' (Aktif)' : '' ?></option><?php endforeach; ?></select></div>
  <div class="col-12"><label>Hari</label><select name="hari" class="form-select"><option>Senin</option><option>Selasa</option><option>Rabu</option><option>Kamis</option><option>Jumat</option><option>Sabtu</option></select></div>
  <div class="col-6"><label>Jam Mulai</label><input type="time" name="jam_mulai" class="form-control" required></div>
  <div class="col-6"><label>Jam Selesai</label><input type="time" name="jam_selesai" class="form-control" required></div>
  <div class="col-12"><label>Ruangan</label><input name="ruangan" class="form-control" required></div>
  <div class="col-12"><button class="btn-genz w-100">Simpan Jadwal</button></div>
</form>
</div>
</div>
<div class="col-lg-8">
<div class="glass-card p-0 overflow-hidden"><div class="table-wrap">
<table class="table-glass"><thead><tr><th>MK</th><th>Dosen</th><th>Hari</th><th>Jam</th><th>Ruang</th><th>Tahun</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr><td><?=e($r['nama_mk'])?></td><td><?=e($r['nama_dosen'])?></td><td><?=e($r['hari'])?></td><td><?=e(substr($r['jam_mulai'],0,5))?>–<?=e(substr($r['jam_selesai'],0,5))?></td><td><?=e($r['ruangan'])?></td><td><?=e($r['tahun_akademik'])?></td>
<td class="td-actions"><a href="?hapus=<?=$r['id_jadwal']?>" class="btn-genz btn-genz-danger btn-genz-sm" onclick="return confirm('Hapus jadwal ini?')">Hapus</a></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
</div>
</div>
<?php render_layout_end(); ?>
