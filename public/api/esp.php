<?php
/**
 * GET api/esp.php?Data=<UID> — dipanggil reader RFID ESP8266 setiap kartu di-tap.
 *
 * Header: X-Device-Key: <config device_key>
 * Respons text/plain "<baris 1 LCD>|<baris 2 LCD>".
 *   200 = presensi tercatat (firmware lalu memicu ESP32-CAM mengambil foto)
 *   4xx = presensi ditolak / kartu belum terdaftar
 */
require __DIR__ . '/../../src/bootstrap.php';
response_format('text');
require_device_key();

$raw = isset($_GET['Data']) ? $_GET['Data'] : (isset($_GET['rfid']) ? $_GET['rfid'] : '');
$rfid = normalize_rfid(is_string($raw) ? $raw : '');
if (!valid_rfid($rfid)) {
    text_response('Kartu Tidak|Terbaca', 400);
}

$result = record_attendance($rfid, null, true);

// Pesan singkat untuk LCD 16x2.
$lcd = [
    'masuk'         => ['Selamat Datang', $result['siswa'] ? $result['siswa']['siswa_nama'] : ''],
    'pulang'        => ['Selamat Jalan', $result['siswa'] ? $result['siswa']['siswa_nama'] : ''],
    'unknown_rfid'  => ['Kartu Tidak', 'Terdaftar'],
    'no_schedule'   => ['Tidak Ada', 'Jadwal Hari Ini'],
    'outside_hours' => ['Di Luar Jam', 'Presensi'],
    'too_early'     => ['Belum Waktunya', 'Pulang'],
    'already_done'  => ['Sudah Absen', 'Hari Ini'],
];
$status = [
    'masuk' => 200, 'pulang' => 200, 'unknown_rfid' => 404, 'no_schedule' => 409,
    'outside_hours' => 409, 'too_early' => 409, 'already_done' => 409,
];

if ($result['code'] === 'unknown_rfid') {
    register_unknown_rfid($rfid);
}

list($line1, $line2) = $lcd[$result['code']];
text_response(str_replace('|', ' ', $line1) . '|' . str_replace('|', ' ', $line2), $status[$result['code']]);
