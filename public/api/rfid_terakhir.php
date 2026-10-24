<?php
/**
 * GET api/rfid_terakhir.php — kartu terakhir yang di-tap di reader namun belum
 * dimiliki siswa mana pun. Dipakai tombol "Ambil dari reader" di form siswa.
 */
require __DIR__ . '/../../src/bootstrap.php';
response_format('json');
require_login();

$row = db_one(
    'SELECT c.rfid_code, c.time_update
     FROM rfid_code c
     LEFT JOIN siswa s ON s.siswa_rfid = c.rfid_code
     WHERE c.used = 0 AND s.siswa_id IS NULL
     ORDER BY c.time_update DESC
     LIMIT 1'
);

if ($row === null) {
    json_response(['status' => false, 'message' => 'Belum ada kartu baru. Tap kartu pada reader terlebih dahulu.']);
}

json_response([
    'status'     => true,
    'rfid'       => $row['rfid_code'],
    'scanned_at' => date('d/m/Y H:i:s', strtotime($row['time_update'])),
]);
