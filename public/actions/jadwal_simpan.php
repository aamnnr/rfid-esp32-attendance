<?php
require __DIR__ . '/../../src/bootstrap.php';
require_login();
require_method('POST');
verify_csrf();

$kelasId = to_id(input('kelas_id'));
if ($kelasId === null || db_value('SELECT 1 FROM kelas WHERE kelas_id = ?', [$kelasId]) === null) {
    flash('warning', 'Data kelas tidak ditemukan.');
    redirect('jadwal.php');
}

$input = isset($_POST['jadwal']) && is_array($_POST['jadwal']) ? $_POST['jadwal'] : [];
$rows = [];

foreach (HARI_JADWAL as $hari) {
    $key = strtolower($hari);
    $masukRaw = isset($input[$key]['masuk']) && is_string($input[$key]['masuk']) ? trim($input[$key]['masuk']) : '';
    $pulangRaw = isset($input[$key]['pulang']) && is_string($input[$key]['pulang']) ? trim($input[$key]['pulang']) : '';

    // Kedua jam kosong = libur.
    if ($masukRaw === '' && $pulangRaw === '') {
        $rows[$hari] = ['00:00:00', '00:00:00'];
        continue;
    }

    $masuk = normalize_time($masukRaw);
    $pulang = normalize_time($pulangRaw);
    if ($masuk === null || $pulang === null) {
        back_with_error("Jam masuk dan jam pulang hari $hari wajib diisi dengan format yang benar.", 'jadwal_form.php', ['kelas_id' => $kelasId]);
    }
    if ($masuk > $pulang) {
        back_with_error("Jam masuk hari $hari tidak boleh setelah jam pulang.", 'jadwal_form.php', ['kelas_id' => $kelasId]);
    }
    $rows[$hari] = [$masuk, $pulang];
}

db_transaction(function () use ($kelasId, $rows) {
    foreach ($rows as $hari => list($masuk, $pulang)) {
        db_exec(
            'INSERT INTO jadwal (kelas_id, jadwal_hari, jadwal_masuk, jadwal_pulang) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE jadwal_masuk = VALUES(jadwal_masuk), jadwal_pulang = VALUES(jadwal_pulang)',
            [$kelasId, $hari, $masuk, $pulang]
        );
    }
});

forget_input();
flash('success', 'Jadwal berhasil diperbarui.');
redirect('jadwal.php');
