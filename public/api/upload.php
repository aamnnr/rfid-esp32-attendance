<?php
/**
 * POST api/upload.php — foto dari ESP32-CAM (multipart, field `imageFile`).
 *
 * Header: X-Device-Key: <config device_key>
 * Foto dipasangkan ke presensi terbaru yang masih menunggu foto.
 */
require __DIR__ . '/../../src/bootstrap.php';
response_format('text');
require_method('POST');
require_device_key();

if (!isset($_FILES['imageFile']) || !is_array($_FILES['imageFile'])) {
    text_response('Error: field imageFile tidak ditemukan.', 400);
}

$photo = store_uploaded_image($_FILES['imageFile'], 'rekap');
if ($photo === null) {
    text_response('Error: file bukan gambar JPG/PNG yang valid atau terlalu besar.', 422);
}

if (!attach_pending_photo($photo)) {
    delete_upload('rekap', $photo);
    text_response('Tidak ada presensi yang menunggu foto.', 404);
}

text_response('OK: foto tersimpan sebagai ' . $photo);
