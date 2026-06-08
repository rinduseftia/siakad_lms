-- Migrasi profil admin & relasi (jalankan sekali di phpMyAdmin jika DB sudah ada)
USE `siakad_lms`;

ALTER TABLE `admin` ADD COLUMN `email` VARCHAR(100) DEFAULT NULL AFTER `nama`;
ALTER TABLE `admin` ADD COLUMN `no_hp` VARCHAR(20) DEFAULT NULL AFTER `email`;
ALTER TABLE `admin` ADD COLUMN `alamat` TEXT DEFAULT NULL AFTER `no_hp`;
ALTER TABLE `admin` ADD COLUMN `foto` VARCHAR(255) DEFAULT NULL AFTER `alamat`;

-- Seed KRS demo (abaikan error jika sudah ada)
INSERT IGNORE INTO `krs` (`nim`,`kode_mk`,`id_tahun`,`status_krs`) VALUES
('2303111235','MSI2101',1,'Disetujui Admin'),
('2303111235','MSI2103',1,'Disetujui Admin');
