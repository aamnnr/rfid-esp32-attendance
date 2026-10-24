<?php
require __DIR__ . '/../../src/bootstrap.php';
require_login();
require_method('POST');
verify_csrf();

$id = to_id(input('id'));
$siswa = $id !== null ? db_one('SELECT siswa_rfid, siswa_foto FROM siswa WHERE siswa_id = ?', [$id]) : null;
if ($siswa === null) {
    flash('warning', 'Data siswa tidak ditemukan.');
    redirect('siswa.php');
}

$photos = db_all('SELECT rekap_photomasuk, rekap_photokeluar FROM rekap WHERE siswa_id = ?', [$id]);

db_transaction(function () use ($id, $siswa) {
    // Rekap ikut terhapus melalui foreign key ON DELETE CASCADE.
    db_exec('DELETE FROM siswa WHERE siswa_id = ?', [$id]);
    db_exec('UPDATE rfid_code SET used = 0 WHERE rfid_code = ?', [$siswa['siswa_rfid']]);
});

delete_upload('siswa', $siswa['siswa_foto']);
foreach ($photos as $photo) {
    delete_upload('rekap', $photo['rekap_photomasuk']);
    delete_upload('rekap', $photo['rekap_photokeluar']);
}

flash('success', 'Data siswa berhasil dihapus.');
redirect('siswa.php');
