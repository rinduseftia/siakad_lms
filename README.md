# SIAKAD & LMS Gen-Z

Sistem Informasi Akademik berdiri sendiri — **PHP Native** + **MySQL** + **Bootstrap 5** + Glassmorphism UI.

## Instalasi Cepat (Mulai dari Nol)

### 1. Letakkan folder di htdocs

Copy/rename folder ini ke `C:\xampp\htdocs\siakad` (atau biarkan di path sekarang — URL menyesuaikan otomatis).

### 2. Import database

1. Buka **phpMyAdmin** → http://localhost/phpmyadmin
2. Import file **`database/siakad.sql`**
3. Database `siakad_lms` akan dibuat beserta data contoh

### 3. Konfigurasi (opsional)

Edit `includes/config.php` jika MySQL pakai password:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'siakad_lms');
```

### 4. Jalankan

```
http://localhost/siakad-kompleks/siakad-baru/siakad/index.php
```

*(Ganti path sesuai lokasi folder di htdocs)*

## Login Default

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | 12345 |
| Dosen | 0030106906 | 0030106906 |
| Mahasiswa | 2303111235 | 2303111235 |

## Struktur Proyek

```
siakad/
├── index.php              # Login
├── logout.php
├── admin/                 # Modul Admin (10 halaman)
├── dosen/                 # Modul Dosen (5 halaman)
├── mahasiswa/             # Modul Mahasiswa (7 halaman)
├── api/
│   └── notifikasi.php     # Polling notifikasi real-time
├── includes/
│   ├── config.php         # DB + base_url otomatis
│   ├── db.php             # Prepared statements
│   ├── auth.php           # Session & role guard
│   ├── functions.php      # IPK, grade, pagination
│   └── layout.php         # Template glassmorphism
├── assets/
│   ├── css/genz-glass.css
│   └── js/
├── database/
│   └── siakad.sql         # Schema + seed data
└── uploads/               # Foto profil
```

## Fitur Utama

- Multi-role: Admin, Dosen, Mahasiswa
- KRS: Pending → Dosen Wali → Admin → Jadwal
- Nilai otomatis (Tugas 20% + Quiz 20% + UTS 30% + UAS 30%)
- Presensi per sesi (Hadir/Izin/Sakit/Alpha)
- Cetak KRS/KHS + Export Excel
- Dark mode, Chart.js, notifikasi polling
- Bulk delete, pagination, filter semester

## Pindah Folder?

Cukup pindahkan folder ke `htdocs/siakad` — path URL otomatis terdeteksi via `base_url()`.
