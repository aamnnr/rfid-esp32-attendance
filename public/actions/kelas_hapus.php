<?php
require __DIR__ . '/../../src/bootstrap.php';
require_login();
require_method('POST');
verify_csrf();

$id = to_id(input('id'));
if ($id === null || db_value('SELECT 1 FROM kelas WHERE kelas_id = ?', [$id]) === null) {
    flash('warning', 'Data kelas tidak ditemukan.');
    redirect('kelas.php');
}

// Foreign key siswa -> kelas bersifat CASCADE, jadi menghapus kelas yang masih
// berisi siswa akan ikut menghapus siswa & seluruh rekapnya. Cegah di sini.
$jumlahSiswa = (int) db_value('SELECT COUNT(*) FROM siswa WHERE kelas_id = ?', [$id]);
if ($jumlahSiswa > 0) {
    flash('danger', "Kelas masih memiliki $jumlahSiswa siswa. Pindahkan atau hapus siswa terlebih dahulu.");
    redirect('kelas.php');
}

db_exec('DELETE FROM kelas WHERE kelas_id = ?', [$id]);

flash('success', 'Data kelas berhasil dihapus.');
redirect('kelas.php');
