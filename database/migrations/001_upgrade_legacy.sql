-- =============================================================================
-- Migrasi dari database lama (dump `labandroid_esprfid` / `camrfidok`)
-- ke skema di database/schema.sql.
--
-- WAJIB backup database terlebih dahulu:
--   mysqldump -u root -p nama_database > backup.sql
--
-- Jalankan per bagian dan periksa hasilnya. Bagian 4 akan gagal jika ada data
-- ganda; jalankan query pengecekan di bawahnya untuk menemukannya.
-- =============================================================================

SET NAMES utf8mb4;

-- 1. Password admin: kolom diperlebar untuk hash bcrypt.
--    Password lama (teks biasa) otomatis di-hash ulang saat admin login pertama kali.
ALTER TABLE `admin` MODIFY `admin_password` VARCHAR(255) NOT NULL;

-- 2. Jenis kelamin: 'M'/'F' -> 'Laki-laki'/'Perempuan'.
ALTER TABLE `siswa` MODIFY `siswa_jeniskelamin` ENUM('M','F','Laki-laki','Perempuan') NOT NULL;
UPDATE `siswa` SET `siswa_jeniskelamin` = 'Laki-laki' WHERE `siswa_jeniskelamin` = 'M';
UPDATE `siswa` SET `siswa_jeniskelamin` = 'Perempuan' WHERE `siswa_jeniskelamin` = 'F';
ALTER TABLE `siswa` MODIFY `siswa_jeniskelamin` ENUM('Laki-laki','Perempuan') NOT NULL;

-- 3. Kolom siswa lainnya. siswa_status ENUM('1','0') diubah lewat VARCHAR
--    agar nilainya tidak berubah menjadi indeks enum.
ALTER TABLE `siswa`
  MODIFY `siswa_rfid` VARCHAR(32) NOT NULL,
  MODIFY `siswa_foto` VARCHAR(255) NULL DEFAULT NULL,
  MODIFY `siswa_status` VARCHAR(1) NOT NULL DEFAULT '1';
ALTER TABLE `siswa` MODIFY `siswa_status` TINYINT(1) NOT NULL DEFAULT 1;
UPDATE `siswa` SET `siswa_rfid` = UPPER(REPLACE(REPLACE(`siswa_rfid`, '''', ''), ' ', ''));

-- 4. Cegah data ganda.
--    Cek dulu:  SELECT siswa_id, rekap_tanggal, COUNT(*) FROM rekap GROUP BY 1, 2 HAVING COUNT(*) > 1;
--               SELECT kelas_id, jadwal_hari, COUNT(*) FROM jadwal GROUP BY 1, 2 HAVING COUNT(*) > 1;
--               SELECT rfid_code, COUNT(*) FROM rfid_code GROUP BY 1 HAVING COUNT(*) > 1;
ALTER TABLE `rekap`
  MODIFY `rekap_keterangan` VARCHAR(50) NULL DEFAULT NULL,
  ADD UNIQUE KEY `uq_rekap_siswa_tanggal` (`siswa_id`, `rekap_tanggal`),
  ADD KEY `idx_rekap_tanggal` (`rekap_tanggal`);
ALTER TABLE `jadwal` ADD UNIQUE KEY `uq_jadwal_kelas_hari` (`kelas_id`, `jadwal_hari`);
ALTER TABLE `rfid_code`
  MODIFY `id` INT NOT NULL AUTO_INCREMENT,
  MODIFY `rfid_code` VARCHAR(32) NOT NULL,
  MODIFY `used` TINYINT(1) NOT NULL DEFAULT 0,
  ADD UNIQUE KEY `uq_rfid_code` (`rfid_code`);

-- 5. Path foto lama ('rekapfoto/xxx.jpg') cukup disimpan nama filenya.
--    Pindahkan juga file fisiknya:
--      rekapfoto/*  -> public/uploads/rekap/
--      image/*      -> public/uploads/siswa/
UPDATE `rekap` SET `rekap_photomasuk`  = SUBSTRING_INDEX(`rekap_photomasuk`, '/', -1)  WHERE `rekap_photomasuk`  LIKE '%/%';
UPDATE `rekap` SET `rekap_photokeluar` = SUBSTRING_INDEX(`rekap_photokeluar`, '/', -1) WHERE `rekap_photokeluar` LIKE '%/%';
UPDATE `siswa` SET `siswa_foto` = NULL WHERE `siswa_foto` = '';

-- 6. Konversi charset ke utf8mb4 agar nama dengan karakter khusus aman.
ALTER TABLE `admin`     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `kelas`     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `jadwal`    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `siswa`     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `rekap`     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `rfid_code` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 7. (Opsional) Tabel sisa yang tidak dipakai aplikasi:
-- DROP TABLE IF EXISTS `data_users`, `users_logs`, `izin`, `libur`;
