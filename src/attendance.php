<?php
/**
 * Aturan presensi. Dipakai bersama oleh perangkat RFID (api/esp.php)
 * dan kiosk web (api/absen.php) agar hasilnya selalu konsisten.
 *
 * Aturan:
 *  - Jadwal hari itu tidak ada / jam masuk = jam pulang  => tidak ada presensi (libur).
 *  - Tap pertama  : dibuka N menit sebelum jam masuk s/d jam pulang.
 *                   <= jam masuk => "Hadir Masuk", setelahnya => "Hadir Terlambat".
 *  - Tap kedua    : mulai jam pulang => "Hadir Pulang".
 */

const KET_MASUK     = 'Hadir Masuk';
const KET_TERLAMBAT = 'Hadir Terlambat';
const KET_PULANG    = 'Hadir Pulang';
const KET_ALPA      = 'Alpa';

const REKAP_STATUSES = [KET_MASUK, KET_TERLAMBAT, KET_PULANG, KET_ALPA];

// Urutan hari sesuai enum kolom `jadwal.jadwal_hari`.
const HARI_JADWAL = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

function find_siswa_by_rfid($rfid)
{
    return db_one(
        'SELECT siswa_id, siswa_nama, kelas_id FROM siswa WHERE siswa_rfid = ? AND siswa_status = 1',
        [$rfid]
    );
}

/**
 * Catat kartu yang belum terdaftar agar bisa dipilih saat menambah siswa.
 */
function register_unknown_rfid($rfid)
{
    db_exec(
        'INSERT INTO rfid_code (rfid_code, used) VALUES (?, 0)
         ON DUPLICATE KEY UPDATE used = 0, time_update = CURRENT_TIMESTAMP',
        [$rfid]
    );
}

function attendance_result($ok, $code, $message, $siswa = null, $keterangan = null)
{
    return [
        'ok'         => $ok,
        'code'       => $code,
        'message'    => $message,
        'siswa'      => $siswa,
        'keterangan' => $keterangan,
    ];
}

/**
 * Proses satu tap kartu.
 *
 * @param string      $rfid        UID kartu (sudah dinormalisasi)
 * @param string|null $photo       Nama file foto yang sudah disimpan (kiosk web)
 * @param bool        $awaitPhoto  true jika foto akan dikirim terpisah oleh ESP32-CAM
 * @return array{ok:bool,code:string,message:string,siswa:?array,keterangan:?string}
 *   code: masuk | pulang | unknown_rfid | no_schedule | outside_hours | too_early | already_done
 */
function record_attendance($rfid, $photo = null, $awaitPhoto = false, ?DateTimeInterface $now = null)
{
    $now = $now ?: new DateTimeImmutable('now');

    $siswa = find_siswa_by_rfid($rfid);
    if ($siswa === null) {
        return attendance_result(false, 'unknown_rfid', 'Kartu RFID belum terdaftar.');
    }

    $jadwal = db_one(
        'SELECT jadwal_id, jadwal_masuk, jadwal_pulang FROM jadwal WHERE kelas_id = ? AND jadwal_hari = ?',
        [(int) $siswa['kelas_id'], nama_hari($now)]
    );
    if ($jadwal === null || $jadwal['jadwal_masuk'] === $jadwal['jadwal_pulang']) {
        return attendance_result(false, 'no_schedule', 'Tidak ada jadwal untuk kelas Anda hari ini.', $siswa);
    }

    $tanggal = $now->format('Y-m-d');
    $jam     = $now->format('H:i:s');
    $masuk   = $jadwal['jadwal_masuk'];
    $pulang  = $jadwal['jadwal_pulang'];
    $pending = $awaitPhoto ? 1 : 0;

    $rekap = db_one(
        'SELECT rekap_id, rekap_masuk, rekap_keluar FROM rekap WHERE siswa_id = ? AND rekap_tanggal = ?',
        [(int) $siswa['siswa_id'], $tanggal]
    );

    // ---- Presensi masuk -----------------------------------------------------
    if ($rekap === null) {
        $buka = (new DateTimeImmutable("$tanggal $masuk"))
            ->modify('-' . (int) config('attendance.early_checkin_minutes', 60) . ' minutes');
        $bukaJam = $buka->format('Y-m-d') < $tanggal ? '00:00:00' : $buka->format('H:i:s');

        if ($jam < $bukaJam || $jam > $pulang) {
            return attendance_result(false, 'outside_hours', sprintf(
                'Di luar jam presensi. Presensi masuk dibuka %s - %s.',
                format_jam($bukaJam),
                format_jam($pulang)
            ), $siswa);
        }

        $keterangan = $jam <= $masuk ? KET_MASUK : KET_TERLAMBAT;
        try {
            db_exec(
                'INSERT INTO rekap (jadwal_id, siswa_id, rekap_tanggal, rekap_masuk, rekap_photomasuk, status1, rekap_keterangan)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [(int) $jadwal['jadwal_id'], (int) $siswa['siswa_id'], $tanggal, $jam, $photo, $pending, $keterangan]
            );
        } catch (mysqli_sql_exception $e) {
            if (!is_duplicate_key_error($e)) {
                throw $e;
            }
            // Dua tap hampir bersamaan: tap pertama sudah tercatat.
            return attendance_result(false, 'already_done', 'Presensi masuk sudah tercatat.', $siswa);
        }

        return attendance_result(true, 'masuk', sprintf(
            'Selamat datang, %s! Presensi masuk tercatat pukul %s (%s).',
            $siswa['siswa_nama'],
            format_jam($jam),
            $keterangan
        ), $siswa, $keterangan);
    }

    // ---- Presensi pulang ----------------------------------------------------
    if ($rekap['rekap_keluar'] !== null) {
        return attendance_result(false, 'already_done', 'Anda sudah melakukan presensi pulang hari ini.', $siswa);
    }

    if ($jam < $pulang) {
        return attendance_result(false, 'too_early', sprintf(
            'Presensi masuk sudah tercatat. Presensi pulang dibuka pukul %s.',
            format_jam($pulang)
        ), $siswa);
    }

    $updated = db_exec(
        'UPDATE rekap SET rekap_keluar = ?, rekap_photokeluar = ?, status2 = ?, rekap_keterangan = ?
         WHERE rekap_id = ? AND rekap_keluar IS NULL',
        [$jam, $photo, $pending, KET_PULANG, (int) $rekap['rekap_id']]
    );
    if ($updated === 0) {
        return attendance_result(false, 'already_done', 'Anda sudah melakukan presensi pulang hari ini.', $siswa);
    }

    return attendance_result(true, 'pulang', sprintf(
        'Selamat jalan, %s! Presensi pulang tercatat pukul %s.',
        $siswa['siswa_nama'],
        format_jam($jam)
    ), $siswa, KET_PULANG);
}

/**
 * Pasangkan foto dari ESP32-CAM ke presensi terbaru yang masih menunggu foto
 * (status1/status2 = 1) dalam batas waktu 'attendance.photo_match_seconds'.
 *
 * @return bool true jika foto berhasil dipasangkan
 */
function attach_pending_photo($filename, ?DateTimeInterface $now = null)
{
    $now = $now ?: new DateTimeImmutable('now');

    $rekap = db_one(
        'SELECT rekap_id, rekap_masuk, rekap_keluar, status1, status2
         FROM rekap
         WHERE rekap_tanggal = ? AND (status1 = 1 OR status2 = 1)
         ORDER BY GREATEST(COALESCE(rekap_masuk, \'00:00:00\'), COALESCE(rekap_keluar, \'00:00:00\')) DESC
         LIMIT 1',
        [$now->format('Y-m-d')]
    );
    if ($rekap === null) {
        return false;
    }

    $isPulang = (int) $rekap['status2'] === 1;
    $eventTime = $isPulang ? $rekap['rekap_keluar'] : $rekap['rekap_masuk'];
    $elapsed = $now->getTimestamp() - strtotime($now->format('Y-m-d') . ' ' . $eventTime);
    if ($elapsed > (int) config('attendance.photo_match_seconds', 120)) {
        return false;
    }

    $sql = $isPulang
        ? 'UPDATE rekap SET rekap_photokeluar = ?, status2 = 0 WHERE rekap_id = ? AND status2 = 1'
        : 'UPDATE rekap SET rekap_photomasuk = ?, status1 = 0 WHERE rekap_id = ? AND status1 = 1';

    return db_exec($sql, [$filename, (int) $rekap['rekap_id']]) > 0;
}
