# SIAKAD — Sistem Informasi Akademik
## Tugas UAS | PHP Native + REST API + MySQL

---

## 📁 Struktur Folder

```
siakad/
├── api/
│   ├── dashboard.php       ← Statistik dashboard
│   ├── login.php           ← Autentikasi admin
│   ├── mahasiswa.php       ← CRUD Mahasiswa (REST API)
│   ├── dosen.php           ← CRUD Dosen (REST API)
│   └── mata_kuliah.php     ← CRUD Mata Kuliah (REST API)
│
├── config/
│   ├── database.php        ← Koneksi MySQL
│   └── cors.php            ← Header CORS + helper response
│
├── assets/
│   ├── style.css           ← Stylesheet utama
│   └── app.js              ← Fetch API JavaScript
│
├── database/
│   └── siakad.sql          ← Script SQL (import ke phpMyAdmin)
│
└── index.html              ← Halaman utama (login + dashboard)
```

---

## 🚀 Cara Menjalankan di XAMPP

### Langkah 1 — Siapkan XAMPP
1. Buka **XAMPP Control Panel**
2. Klik **Start** pada **Apache** dan **MySQL**

### Langkah 2 — Import Database
1. Buka browser → http://localhost/phpmyadmin
2. Klik **New** → buat database baru bernama `siakad`
3. Pilih tab **Import** → klik **Choose File**
4. Pilih file `database/siakad.sql` → klik **Go**

### Langkah 3 — Salin Project ke htdocs
1. Salin seluruh folder `siakad/` ke:
   - Windows: `C:\xampp\htdocs\siakad\`
   - Mac/Linux: `/opt/lampp/htdocs/siakad/`

### Langkah 4 — Buka di Browser
```
http://localhost/siakad/
```

**Login default:**
- Username: `admin`
- Password: `admin123`

---

## 🔌 Endpoint REST API

| Method | URL | Fungsi |
|--------|-----|--------|
| GET    | /api/mahasiswa.php | Tampil semua mahasiswa |
| GET    | /api/mahasiswa.php?id=1 | Tampil satu mahasiswa |
| POST   | /api/mahasiswa.php | Tambah mahasiswa baru |
| PUT    | /api/mahasiswa.php?id=1 | Update mahasiswa |
| DELETE | /api/mahasiswa.php?id=1 | Hapus mahasiswa |
| GET    | /api/dosen.php | Tampil semua dosen |
| POST   | /api/dosen.php | Tambah dosen |
| PUT    | /api/dosen.php?id=1 | Update dosen |
| DELETE | /api/dosen.php?id=1 | Hapus dosen |
| GET    | /api/mata_kuliah.php | Tampil semua mata kuliah |
| POST   | /api/mata_kuliah.php | Tambah mata kuliah |
| PUT    | /api/mata_kuliah.php?id=1 | Update mata kuliah |
| DELETE | /api/mata_kuliah.php?id=1 | Hapus mata kuliah |
| GET    | /api/dashboard.php | Statistik dashboard |
| POST   | /api/login.php | Login admin |

---

## 🧪 Testing API dengan Postman / Browser

### GET — Tampil Semua Mahasiswa
```
GET http://localhost/siakad/api/mahasiswa.php
```

### POST — Tambah Mahasiswa
```
POST http://localhost/siakad/api/mahasiswa.php
Content-Type: application/json

{
  "nim": "2303111239",
  "nama": "Budi Santoso",
  "program_studi": "Sistem Informasi",
  "angkatan": 2023,
  "email": "budi@student.ac.id",
  "no_hp": "081234567890"
}
```

### PUT — Edit Mahasiswa (id=1)
```
PUT http://localhost/siakad/api/mahasiswa.php?id=1
Content-Type: application/json

{
  "nim": "2303111235",
  "nama": "Poltak Simanjuntak Updated",
  "program_studi": "Sistem Informasi",
  "angkatan": 2023
}
```

### DELETE — Hapus Mahasiswa (id=1)
```
DELETE http://localhost/siakad/api/mahasiswa.php?id=1
```

---

## ⚙️ Konfigurasi Database

Edit file `config/database.php` jika perlu:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // username MySQL kamu
define('DB_PASS', '');       // password MySQL (default XAMPP = kosong)
define('DB_NAME', 'siakad');
```

---

## 🛠️ Teknologi yang Digunakan

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 8.x Native
- **Database**: MySQL via phpMyAdmin
- **Konsep**: REST API (GET / POST / PUT / DELETE)
- **Server**: Apache (XAMPP)

---

© 2026 SIAKAD — Sistem Informasi Akademik
