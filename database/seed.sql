-- =============================================================================
-- Data awal untuk pengembangan / demo. Jalankan setelah schema.sql.
--
-- Akun admin default: username `admin`, password `admin`.
-- SEGERA ganti password ini setelah instalasi di server produksi.
-- =============================================================================

SET NAMES utf8mb4;

INSERT INTO `admin` (`admin_username`, `admin_password`, `admin_nama`) VALUES
('admin', '$2y$12$FQMQIZa6CBIPAj2gZAiHBeKf12tVH5jfDPJM1arHIM4fWjloglN7C', 'Administrator');

INSERT INTO `kelas` (`kelas_id`, `kelas_nama`) VALUES
(1, 'X TKRO 1'),
(2, 'X TKRO 2'),
(3, 'X TKRO 3');

-- Senin-Jumat 07:00-15:00, Sabtu & Minggu libur.
INSERT INTO `jadwal` (`kelas_id`, `jadwal_hari`, `jadwal_masuk`, `jadwal_pulang`)
SELECT k.kelas_id, h.hari, h.masuk, h.pulang
FROM `kelas` k
CROSS JOIN (
  SELECT 'Senin' AS hari, '07:00:00' AS masuk, '15:00:00' AS pulang UNION ALL
  SELECT 'Selasa', '07:00:00', '15:00:00' UNION ALL
  SELECT 'Rabu',   '07:00:00', '15:00:00' UNION ALL
  SELECT 'Kamis',  '07:00:00', '15:00:00' UNION ALL
  SELECT 'Jumat',  '07:00:00', '15:00:00' UNION ALL
  SELECT 'Sabtu',  '00:00:00', '00:00:00' UNION ALL
  SELECT 'Minggu', '00:00:00', '00:00:00'
) h;

INSERT INTO `siswa` (`kelas_id`, `siswa_rfid`, `siswa_nama`, `siswa_absen`, `siswa_jeniskelamin`,
                     `siswa_lahir`, `siswa_nomorhp`, `siswa_alamat`) VALUES
(1, 'E3E64D15', 'Siswa Contoh', '1', 'Laki-laki', '2008-07-17', '6281234567890', 'Jakarta');
