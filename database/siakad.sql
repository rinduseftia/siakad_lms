-- ============================================================
--  SIAKAD  –  Database Revisi (v2)
--  Perubahan:
--    1. Tabel `users`  →  central authentication
--    2. mahasiswa.nim  →  PRIMARY KEY
--    3. mahasiswa.user_id  →  FOREIGN KEY → users.id
--    4. dosen.user_id      →  FOREIGN KEY → users.id
--    5. Default username & password = NIM / NIDN / 'admin'
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 1. TABEL USERS  (central authentication)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE `users` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `username`   varchar(50)  NOT NULL,
  `password`   varchar(255) NOT NULL,
  `role`       enum('admin','mahasiswa','dosen') NOT NULL,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 2. TABEL ADMIN
-- ─────────────────────────────────────────────────────────────
CREATE TABLE `admin` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    int(11)      DEFAULT NULL,
  `username`   varchar(50)  NOT NULL,
  `password`   varchar(255) NOT NULL,
  `nama`       varchar(100) DEFAULT NULL,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  CONSTRAINT `fk_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 3. TABEL DOSEN  (+user_id FK)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE `dosen` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    int(11)      DEFAULT NULL,
  `nidn`       varchar(20)  NOT NULL,
  `nama`       varchar(100) NOT NULL,
  `email`      varchar(100) DEFAULT NULL,
  `no_hp`      varchar(20)  DEFAULT NULL,
  `status`     enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nidn` (`nidn`),
  CONSTRAINT `fk_dosen_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 4. TABEL MAHASISWA  (nim = PRIMARY KEY, +user_id FK)
--    Kolom `id` AUTO_INCREMENT dihapus sesuai instruksi dosen:
--    NIM sebagai primary key, user_id sebagai foreign key
-- ─────────────────────────────────────────────────────────────
CREATE TABLE `mahasiswa` (
  `nim`          varchar(20)  NOT NULL,
  `user_id`      int(11)      DEFAULT NULL,
  `nama`         varchar(100) NOT NULL,
  `jur`          varchar(50)  NOT NULL DEFAULT '',
  `program_studi`varchar(100) DEFAULT NULL,
  `email`        varchar(100) DEFAULT NULL,
  `agama`        varchar(20)  NOT NULL DEFAULT '',
  `status`       varchar(20)  NOT NULL DEFAULT '',
  `jk`           varchar(20)  NOT NULL DEFAULT '',
  `tmp_lahir`    varchar(100) NOT NULL DEFAULT '',
  `tgl_lahir`    date         DEFAULT NULL,
  `alamat`       text         NOT NULL,
  `created_at`   timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`nim`),
  CONSTRAINT `fk_mahasiswa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 5. TABEL MATA KULIAH  (tidak berubah)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE `mata_kuliah` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `kode`       varchar(20)  NOT NULL,
  `nama`       varchar(150) NOT NULL,
  `sks`        int(11)      DEFAULT 3,
  `semester`   int(11)      DEFAULT NULL,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- DATA AWAL
-- Urutan INSERT penting: users dulu (karena FK)
-- ─────────────────────────────────────────────────────────────

-- users: admin(1), dosen(2-3), mahasiswa(4-6)
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin',      '12345',      'admin'),
(2, '0030106906', '0030106906', 'dosen'),
(3, '0031126318', '0031126318', 'dosen'),
(4, '2303111235', '2303111235', 'mahasiswa'),
(5, '2303111236', '2303111236', 'mahasiswa'),
(6, '2303111237', '2303111237', 'mahasiswa');

INSERT INTO `admin` (`id`, `user_id`, `username`, `password`, `nama`) VALUES
(1, 1, 'admin', '12345', 'Administrator');

-- dosen: user_id → FK ke users.id
INSERT INTO `dosen` (`id`, `user_id`, `nidn`, `nama`, `email`, `status`) VALUES
(1, 2, '0030106906', 'Joko Risanto','joko@dosen.ac.id','Aktif'),
(2, 3, '0031126318', 'Zaiful Bahri, S.Si., M.Kom','zaiful@dosen.ac.id', 'Aktif');

-- mahasiswa: nim = PK, user_id → FK ke users.id
INSERT INTO `mahasiswa` (`nim`, `user_id`, `nama`, `jur`, `program_studi`, `email`, `agama`, `status`, `jk`, `tmp_lahir`, `tgl_lahir`, `alamat`) VALUES
('2303111235', 4, 'Poltak Simanjuntak', 'Informatika', 'Sistem Informasi', 'poltak@student.ac.id', 'Islam', 'Aktif', 'Laki-laki',  'Medan',    '2003-01-15', 'Jl. Merdeka No. 1'),
('2303111236', 5, 'Fahri Eka',          'Informatika', 'Sistem Informasi', 'fahri@student.ac.id',  'Islam', 'Aktif', 'Laki-laki',  'Pekanbaru', '2003-05-20', 'Jl. Sudirman No. 5'),
('2303111237', 6, 'Yuli Afandi',        'Informatika', 'Sistem Informasi', 'yuli@student.ac.id',   'Islam', 'Aktif', 'Laki-laki',  'Padang',    '2003-08-10', 'Jl. Imam Bonjol No. 3');

INSERT INTO `mata_kuliah` (`id`, `kode`, `nama`, `sks`, `semester`) VALUES
(1, 'MSI2203', 'PSI Berbasis Web',            3, 3),
(2, 'MSI3105', 'Aplikasi Perangkat Bergerak', 3, 5);

-- AUTO_INCREMENT reset
ALTER TABLE `users`       MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `admin`       MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `dosen`       MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `mata_kuliah` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

COMMIT;