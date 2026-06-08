-- Migrasi arsitektur User Management (jalankan sekali)
USE `siakad_lms`;

-- Status akun pada tabel autentikasi
ALTER TABLE `users`
    ADD COLUMN `status` ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif' AFTER `role`;

-- No HP mahasiswa untuk profil
ALTER TABLE `mahasiswa`
    ADD COLUMN `no_hp` VARCHAR(20) DEFAULT NULL AFTER `email`;

-- Buat akun users untuk dosen yang belum punya user_id
INSERT INTO `users` (`username`, `password`, `role`, `status`)
SELECT d.`nidn`, d.`nidn`, 'dosen',
       IF(d.`status` = 'Aktif', 'Aktif', 'Nonaktif')
FROM `dosen` d
WHERE d.`user_id` IS NULL
  AND NOT EXISTS (SELECT 1 FROM `users` u WHERE u.`username` = d.`nidn`);

-- Hubungkan dosen ke akun users
UPDATE `dosen` d
JOIN `users` u ON u.`username` = d.`nidn` AND u.`role` = 'dosen'
SET d.`user_id` = u.`id`
WHERE d.`user_id` IS NULL;
