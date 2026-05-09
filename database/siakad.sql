-- ============================================
-- DATABASE: siakad
-- Sistem Informasi Akademik
-- ============================================

CREATE DATABASE IF NOT EXISTS siakad CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE siakad;

-- Tabel Admin (untuk login)
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Mahasiswa
CREATE TABLE IF NOT EXISTS mahasiswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    program_studi VARCHAR(100),
    angkatan YEAR,
    email VARCHAR(100),
    no_hp VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Dosen
CREATE TABLE IF NOT EXISTS dosen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nidn VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    no_hp VARCHAR(20),
    status ENUM('Aktif','Tidak Aktif') DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Mata Kuliah
CREATE TABLE IF NOT EXISTS mata_kuliah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(150) NOT NULL,
    sks INT DEFAULT 3,
    semester INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- DATA AWAL
-- ============================================

-- Admin default: username=admin, password=admin123
INSERT INTO admin (username, password, nama) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');

-- Data Mahasiswa
INSERT INTO mahasiswa (nim, nama, program_studi, angkatan, email) VALUES
('2303111235', 'Poltak Simanjuntak', 'Sistem Informasi', 2023, 'poltak@student.ac.id'),
('2303111236', 'Fahri Eka', 'Sistem Informasi', 2023, 'fahri@student.ac.id'),
('2303111237', 'Yuli Afandi', 'Sistem Informasi', 2023, 'yuli@student.ac.id'),
('2303111238', 'Rini Syahrini', 'Sistem Informasi', 2023, 'rini@student.ac.id');

-- Data Dosen
INSERT INTO dosen (nidn, nama, email, status) VALUES
('0030106906', 'Joko Risanto', 'joko@dosen.ac.id', 'Aktif'),
('0031126318', 'Zaiful Bahri, S.Si., M.Kom', 'zaiful@dosen.ac.id', 'Aktif');

-- Data Mata Kuliah
INSERT INTO mata_kuliah (kode, nama, sks, semester) VALUES
('MSI2203', 'PSI Berbasis Web', 3, 3),
('MSI3105', 'Aplikasi Perangkat Bergerak', 3, 5);
