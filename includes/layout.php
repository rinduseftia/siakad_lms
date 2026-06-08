<?php
require_once __DIR__ . '/config.php';

function render_layout_start(string $title, string $role, array $menu): void
{
    $user = current_user();
    $flash = get_flash();
    ?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — SIAKAD LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/genz-glass.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="genz-app">
<aside class="glass-sidebar" id="sidebar">
    <a href="<?= base_url("$role/dashboard.php") ?>" class="genz-brand">SIAKAD</a>
    <span class="genz-brand-sub">Learning Management</span>
    <p class="genz-title mb-3"><?= e(strtoupper($role)) ?></p>
    <ul class="nav-genz">
        <?php foreach ($menu as $item): ?>
        <li><a href="<?= base_url($item['url']) ?>" class="<?= ($item['active'] ?? false) ? 'active' : '' ?>">
            <?= e($item['label']) ?>
        </a></li>
        <?php endforeach; ?>
    </ul>
    <div class="dark-toggle mt-auto pt-3">
        <label><input type="checkbox" id="darkModeToggle"> Mode Gelap</label>
    </div>
    <a href="<?= base_url('logout.php') ?>" class="btn-genz-outline mt-2 text-center d-block">Keluar</a>
</aside>
<main class="genz-main">
    <div class="genz-topbar">
        <div>
            <p class="genz-title mb-0">Portal Akademik</p>
            <h1 class="h4 fw-bold mb-0"><?= e($title) ?></h1>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div id="notifBell" class="notif-bell position-relative" title="Notifikasi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span id="notifCount" class="notif-badge" style="display:none">0</span>
            </div>
            <div class="weather-widget no-print">
                <div class="time" id="liveClock">--:--</div>
                <div class="date-label" id="liveDate">—</div>
            </div>
            <div class="user-info-nav d-flex align-items-center gap-2">
                <img src="<?= e(user_avatar_url($user)) ?>" alt="Avatar" class="nav-avatar">
                <div class="text-end">
                    <div class="fw-bold"><?= e($user['nama'] ?? '') ?></div>
                    <div class="small text-muted"><?= e(user_subtitle($user)) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php if ($flash): ?>
    <div class="alert-genz <?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>
    <div id="notifDropdown" class="glass-card position-absolute end-0 mt-1 me-4 d-none" style="z-index:200;max-width:320px;right:2rem"></div>
<?php
}

function render_layout_end(): void
{
    ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.APP_BASE = <?= json_encode(base_url()) ?>;</script>
<script src="<?= asset('js/dark-mode.js') ?>"></script>
<script src="<?= asset('js/bulk-select.js') ?>"></script>
<script src="<?= asset('js/notifications.js') ?>"></script>
<script src="<?= asset('js/nilai-calc.js') ?>"></script>
</body>
</html>
<?php
}

function admin_menu(string $active = ''): array
{
    $items = [
        ['url' => 'admin/dashboard.php', 'label' => 'Dashboard'],
        ['url' => 'admin/mahasiswa.php', 'label' => 'Mahasiswa'],
        ['url' => 'admin/dosen.php', 'label' => 'Dosen'],
        ['url' => 'admin/matakuliah.php', 'label' => 'Mata Kuliah'],
        ['url' => 'admin/krs.php', 'label' => 'Persetujuan KRS'],
        ['url' => 'admin/jadwal.php', 'label' => 'Jadwal Kuliah'],
        ['url' => 'admin/nilai.php', 'label' => 'Monitoring Nilai'],
        ['url' => 'admin/presensi.php', 'label' => 'Presensi'],
        ['url' => 'admin/pengumuman.php', 'label' => 'Pengumuman'],
        ['url' => 'admin/users.php', 'label' => 'User Management'],
        ['url' => 'admin/profil.php', 'label' => 'Profil Saya'],
    ];
    foreach ($items as &$i) {
        $i['active'] = str_contains($active, basename($i['url'], '.php'));
    }
    return $items;
}

function dosen_menu(string $active = ''): array
{
    $items = [
        ['url' => 'dosen/dashboard.php', 'label' => 'Dashboard'],
        ['url' => 'dosen/perwalian.php', 'label' => 'Mahasiswa Perwalian'],
        ['url' => 'dosen/krs.php', 'label' => 'Persetujuan KRS'],
        ['url' => 'dosen/nilai.php', 'label' => 'Input Nilai'],
        ['url' => 'dosen/presensi.php', 'label' => 'Presensi'],
        ['url' => 'dosen/profil.php', 'label' => 'Profil Saya'],
    ];
    foreach ($items as &$i) {
        $i['active'] = str_contains($active, basename($i['url'], '.php'));
    }
    return $items;
}

function mhs_menu(string $active = ''): array
{
    $items = [
        ['url' => 'mahasiswa/dashboard.php', 'label' => 'Dashboard'],
        ['url' => 'mahasiswa/krs.php', 'label' => 'KRS'],
        ['url' => 'mahasiswa/jadwal.php', 'label' => 'Jadwal Kuliah'],
        ['url' => 'mahasiswa/nilai.php', 'label' => 'Nilai & KHS'],
        ['url' => 'mahasiswa/presensi.php', 'label' => 'Presensi'],
        ['url' => 'mahasiswa/cetak.php', 'label' => 'Cetak Dokumen'],
        ['url' => 'mahasiswa/profil.php', 'label' => 'Profil Saya'],
    ];
    foreach ($items as &$i) {
        $i['active'] = str_contains($active, basename($i['url'], '.php'));
    }
    return $items;
}
