<?php
require __DIR__ . '/../../src/bootstrap.php';
require_login();
require_method('POST');
verify_csrf();

$id = to_id(input('id'));
$nama = input('kelasNama');
$formQuery = $id !== null ? ['id' => $id] : [];

if ($id !== null && db_value('SELECT 1 FROM kelas WHERE kelas_id = ?', [$id]) === null) {
    flash('warning', 'Data kelas tidak ditemukan.');
    redirect('kelas.php');
}
if (str_length($nama) < 2 || str_length($nama) > 50) {
    back_with_error('Nama kelas minimal 2 dan maksimal 50 karakter.', 'kelas_form.php', $formQuery);
}
if (db_value('SELECT 1 FROM kelas WHERE kelas_nama = ? AND kelas_id <> ?', [$nama, (int) $id]) !== null) {
    back_with_error('Nama kelas sudah terdaftar.', 'kelas_form.php', $formQuery);
}

try {
    db_transaction(function () use ($id, $nama) {
        if ($id !== null) {
            db_exec('UPDATE kelas SET kelas_nama = ? WHERE kelas_id = ?', [$nama, $id]);
            return;
        }

        db_exec('INSERT INTO kelas (kelas_nama) VALUES (?)', [$nama]);
        $kelasId = db_insert_id();
        // Jadwal awal: semua hari libur (jam masuk = jam pulang).
        foreach (HARI_JADWAL as $hari) {
            db_exec(
                "INSERT INTO jadwal (kelas_id, jadwal_hari, jadwal_masuk, jadwal_pulang) VALUES (?, ?, '00:00:00', '00:00:00')",
                [$kelasId, $hari]
            );
        }
    });
} catch (mysqli_sql_exception $e) {
    if (is_duplicate_key_error($e)) {
        back_with_error('Nama kelas sudah terdaftar.', 'kelas_form.php', $formQuery);
    }
    throw $e;
}

forget_input();
flash('success', $id === null ? 'Data kelas berhasil ditambahkan.' : 'Data kelas berhasil diperbarui.');
redirect('kelas.php');
