<?php
/**
 * POST api/absen.php — presensi dari kiosk web (absen.php).
 *
 * Body (form-data): rfid, image (data URL JPEG/PNG)
 * Sengaja tanpa token CSRF: halaman kiosk dibiarkan terbuka seharian sehingga
 * token sesi akan kedaluwarsa, dan presensi tetap membutuhkan kode kartu yang valid.
 * Respons JSON: {status: bool, message: string}
 */
require __DIR__ . '/../../src/bootstrap.php';
response_format('json');
require_method('POST');

$rfid = normalize_rfid(input('rfid'));
if (!valid_rfid($rfid)) {
    json_response(['status' => false, 'message' => 'Kode kartu tidak valid.'], 422);
}

$image = isset($_POST['image']) && is_string($_POST['image']) ? $_POST['image'] : '';
if (!preg_match('#^data:image/(?:jpeg|png);base64,#', $image, $prefix)) {
    json_response(['status' => false, 'message' => 'Foto tidak terbaca. Pastikan kamera aktif.'], 422);
}
$bytes = base64_decode(substr($image, strlen($prefix[0])), true);
$photo = $bytes === false ? null : store_image_bytes($bytes, 'rekap');
if ($photo === null) {
    json_response(['status' => false, 'message' => 'Foto tidak valid atau terlalu besar.'], 422);
}

$result = record_attendance($rfid, $photo);
if (!$result['ok']) {
    delete_upload('rekap', $photo);
    if ($result['code'] === 'unknown_rfid') {
        register_unknown_rfid($rfid);
    }
}

json_response(['status' => $result['ok'], 'message' => $result['message']], $result['ok'] ? 200 : 409);
