<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_role('dosen');

$dosen = db_fetch_one('SELECT nidn FROM dosen WHERE user_id=?', 'i', [current_user()['id']]);
$rows = db_fetch_all(
    "SELECT m.*, (SELECT COUNT(*) FROM krs k WHERE k.nim=m.nim AND k.status_krs='Pending') AS krs_pending
     FROM mahasiswa m WHERE m.id_dosen_wali=? ORDER BY m.nama",
    's', [$dosen['nidn'] ?? '']
);

render_layout_start('Mahasiswa Perwalian', 'dosen', dosen_menu('perwalian'));
?>
<div class="glass-card">
    <table class="table-glass">
        <thead><tr><th>NIM</th><th>Nama</th><th>Semester</th><th>IPK</th><th>KRS Pending</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['nim']) ?></td><td><?= e($r['nama']) ?></td>
            <td><?= e($r['semester_sekarang']) ?></td><td><?= hitung_ipk($r['nim']) ?></td>
            <td><?= $r['krs_pending'] ? '<span class="badge-status badge-pending">'.$r['krs_pending'].'</span>' : '—' ?></td>
            <td><?= e($r['status']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php render_layout_end(); ?>
