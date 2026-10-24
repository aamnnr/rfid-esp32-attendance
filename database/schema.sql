-- =============================================================================
-- Skema database Sistem Presensi RFID + ESP32-CAM
-- MySQL 5.7+ / MariaDB 10.2+
--
-- Pemakaian (database baru):
--   CREATE DATABASE presensi_rfid CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   mysql -u root -p presensi_rfid < database/schema.sql
--   mysql -u root -p presensi_rfid < database/seed.sql
--
-- Untuk database lama (dump labandroid_esprfid), gunakan
-- database/migrations/001_upgrade_legacy.sql.
-- =============================================================================

SET NAMES utf8mb4;

-- Akun administrator panel web. Password disimpan dengan password_hash().
CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id`       INT NOT NULL AUTO_INCREMENT,
  `admin_username` VARCHAR(30)  NOT NULL,
  `admin_password` VARCHAR(255) NOT NULL,
  `admin_nama`     VARCHAR(50)  NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_admin_username` (`admin_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kelas` (
  `kelas_id`   INT NOT NULL AUTO_INCREMENT,
  `kelas_nama` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`kelas_id`),
  UNIQUE KEY `uq_kelas_nama` (`kelas_nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jadwal per kelas per hari. jadwal_masuk = jadwal_pulang berarti libur.
CREATE TABLE IF NOT EXISTS `jadwal` (
  `jadwal_id`     INT NOT NULL AUTO_INCREMENT,
  `kelas_id`      INT NOT NULL,
  `jadwal_hari`   ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
  `jadwal_masuk`  TIME NOT NULL DEFAULT '00:00:00',
  `jadwal_pulang` TIME NOT NULL DEFAULT '00:00:00',
  PRIMARY KEY (`jadwal_id`),
  UNIQUE KEY `uq_jadwal_kelas_hari` (`kelas_id`, `jadwal_hari`),
  CONSTRAINT `fk_jadwal_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`kelas_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `siswa` (
  `siswa_id`           INT NOT NULL AUTO_INCREMENT,
  `kelas_id`           INT NOT NULL,
  `siswa_rfid`         VARCHAR(32)  NOT NULL COMMENT 'UID kartu (heksadesimal, huruf besar)',
  `siswa_nama`         VARCHAR(50)  NOT NULL,
  `siswa_absen`        VARCHAR(16)  NOT NULL,
  `siswa_jeniskelamin` ENUM('Laki-laki','Perempuan') NOT NULL,
  `siswa_lahir`        DATE         NOT NULL,
  `siswa_nomorhp`      VARCHAR(20)  NOT NULL,
  `siswa_alamat`       VARCHAR(500) NOT NULL,
  `siswa_foto`         VARCHAR(255) DEFAULT NULL COMMENT 'Nama file di public/uploads/siswa',
  `siswa_status`       TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1 = aktif',
  PRIMARY KEY (`siswa_id`),
  UNIQUE KEY `uq_siswa_rfid` (`siswa_rfid`),
  UNIQUE KEY `uq_siswa_absen` (`siswa_absen`),
  KEY `idx_siswa_kelas` (`kelas_id`),
  CONSTRAINT `fk_siswa_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`kelas_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Satu baris per siswa per tanggal.
-- status1/status2 = 1 berarti presensi masuk/pulang masih menunggu foto dari ESP32-CAM.
CREATE TABLE IF NOT EXISTS `rekap` (
  `rekap_id`          BIGINT NOT NULL AUTO_INCREMENT,
  `jadwal_id`         INT    NOT NULL,
  `siswa_id`          INT    NOT NULL,
  `rekap_tanggal`     DATE   NOT NULL,
  `rekap_masuk`       TIME         DEFAULT NULL,
  `rekap_keluar`      TIME         DEFAULT NULL,
  `rekap_photomasuk`  VARCHAR(255) DEFAULT NULL COMMENT 'Nama file di public/uploads/rekap',
  `status1`           TINYINT(1)   NOT NULL DEFAULT 0,
  `rekap_photokeluar` VARCHAR(255) DEFAULT NULL COMMENT 'Nama file di public/uploads/rekap',
  `status2`           TINYINT(1)   NOT NULL DEFAULT 0,
  `rekap_keterangan`  VARCHAR(50)  DEFAULT NULL COMMENT 'Hadir Masuk | Hadir Terlambat | Hadir Pulang | Alpa',
  PRIMARY KEY (`rekap_id`),
  UNIQUE KEY `uq_rekap_siswa_tanggal` (`siswa_id`, `rekap_tanggal`),
  KEY `idx_rekap_tanggal` (`rekap_tanggal`),
  KEY `idx_rekap_jadwal` (`jadwal_id`),
  CONSTRAINT `fk_rekap_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`siswa_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rekap_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`jadwal_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kartu yang pernah di-tap tetapi belum terdaftar; dipakai saat menambah siswa.
CREATE TABLE IF NOT EXISTS `rfid_code` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `rfid_code`   VARCHAR(32) NOT NULL,
  `used`        TINYINT(1)  NOT NULL DEFAULT 0,
  `time_update` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rfid_code` (`rfid_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
