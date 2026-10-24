<?php
require __DIR__ . '/../../src/bootstrap.php';
require_login();
require_method('POST');
verify_csrf();

$id = to_id(input('id'));
$status = input('rekapStatus');

if ($id === null || db_value('SELECT 1 FROM rekap WHERE rekap_id = ?', [$id]) === null) {
    flash('warning', 'Data rekap tidak ditemukan.');
    redirect('rekap.php');
}
if (!in_array($status, REKAP_STATUSES, true)) {
    back_with_error('Status presensi tidak valid.', 'rekap_form.php', ['id' => $id]);
}

db_exec('UPDATE rekap SET rekap_keterangan = ? WHERE rekap_id = ?', [$status, $id]);

flash('success', 'Status presensi berhasil diperbarui.');
redirect('rekap_form.php', ['id' => $id]);
