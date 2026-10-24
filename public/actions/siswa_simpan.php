<?php
require __DIR__ . '/../../src/bootstrap.php';
require_login();
require_method('POST');
verify_csrf();

$id = to_id(input('id'));
$formPath = 'siswa_form.php';
$formQuery = $id !== null ? ['id' => $id] : [];

$existing = null;
if ($id !== null) {
    $existing = db_one('SELECT siswa_id, siswa_rfid, siswa_foto FROM siswa WHERE siswa_id = ?', [$id]);
    if ($existing === null) {
        flash('warning', 'Data siswa tidak ditemukan.');
        redirect('siswa.php');
    }
}

$data = [
    'rfid'   => normalize_rfid(input('siswaRfid')),
    'absen'  => input('siswaAbsen'),
    'nama'   => input('siswaNama'),
    'kelas'  => to_id(input('siswaKelas')),
    'jk'     => input('siswaJK'),
    'lahir'  => input('siswaTgl'),
    'nohp'   => preg_replace('/\D/', '', input('siswaNohp')),
    'alamat' => input('siswaAlamat'),
];

// ---- Validasi --------------------------------------------------------------
$error = null;
if (!valid_rfid($data['rfid'])) {
    $error = 'Kode RFID tidak valid (4-32 karakter huruf/angka).';
} elseif (!preg_match('/^\d{1,16}$/', $data['absen'])) {
    $error = 'Nomor absen harus berupa angka (maksimal 16 digit).';
} elseif (str_length($data['nama']) < 2 || str_length($data['nama']) > 50) {
    $error = 'Nama minimal 2 dan maksimal 50 karakter.';
} elseif ($data['kelas'] === null || db_value('SELECT 1 FROM kelas WHERE kelas_id = ?', [$data['kelas']]) === null) {
    $error = 'Kelas tidak valid.';
} elseif (!in_array($data['jk'], JENIS_KELAMIN, true)) {
    $error = 'Jenis kelamin tidak valid.';
} elseif (!valid_date($data['lahir']) || $data['lahir'] > date('Y-m-d')) {
    $error = 'Tanggal lahir tidak valid.';
} elseif (!valid_phone($data['nohp'])) {
    $error = 'Nomor telepon harus 6-19 digit angka.';
} elseif (str_length($data['alamat']) < 4 || str_length($data['alamat']) > 500) {
    $error = 'Alamat minimal 4 dan maksimal 500 karakter.';
} elseif (db_value('SELECT 1 FROM siswa WHERE siswa_rfid = ? AND siswa_id <> ?', [$data['rfid'], (int) $id]) !== null) {
    $error = 'Kode RFID sudah dipakai siswa lain.';
} elseif (db_value('SELECT 1 FROM siswa WHERE siswa_absen = ? AND siswa_id <> ?', [$data['absen'], (int) $id]) !== null) {
    $error = 'Nomor absen sudah terdaftar.';
}

$foto = null;
if ($error === null && has_uploaded_file('siswaFoto')) {
    $foto = store_uploaded_image($_FILES['siswaFoto'], 'siswa');
    if ($foto === null) {
        $error = 'Foto tidak valid. Gunakan JPG/PNG dengan ukuran maksimal '
            . (int) (config('upload.max_bytes') / 1024 / 1024) . ' MB.';
    }
}

if ($error !== null) {
    back_with_error($error, $formPath, $formQuery);
}

// ---- Simpan ----------------------------------------------------------------
$params = [
    $data['kelas'], $data['rfid'], $data['nama'], $data['absen'], $data['jk'],
    $data['lahir'], $data['nohp'], $data['alamat'],
];

try {
    db_transaction(function () use ($id, $existing, $params, $foto, $data) {
        if ($id === null) {
            db_exec(
                'INSERT INTO siswa (kelas_id, siswa_rfid, siswa_nama, siswa_absen, siswa_jeniskelamin, siswa_lahir,
                                    siswa_nomorhp, siswa_alamat, siswa_foto)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array_merge($params, [$foto])
            );
        } else {
            db_exec(
                'UPDATE siswa SET kelas_id = ?, siswa_rfid = ?, siswa_nama = ?, siswa_absen = ?, siswa_jeniskelamin = ?,
                                  siswa_lahir = ?, siswa_nomorhp = ?, siswa_alamat = ?, siswa_foto = COALESCE(?, siswa_foto)
                 WHERE siswa_id = ?',
                array_merge($params, [$foto, $id])
            );
            if ($existing['siswa_rfid'] !== $data['rfid']) {
                db_exec('UPDATE rfid_code SET used = 0 WHERE rfid_code = ?', [$existing['siswa_rfid']]);
            }
        }
        db_exec('UPDATE rfid_code SET used = 1 WHERE rfid_code = ?', [$data['rfid']]);
    });
} catch (mysqli_sql_exception $e) {
    delete_upload('siswa', $foto);
    if (is_duplicate_key_error($e)) {
        back_with_error('Kode RFID atau nomor absen sudah terdaftar.', $formPath, $formQuery);
    }
    throw $e;
}

if ($foto !== null && $existing !== null) {
    delete_upload('siswa', $existing['siswa_foto']);
}

forget_input();
flash('success', $id === null ? 'Data siswa berhasil ditambahkan.' : 'Data siswa berhasil diperbarui.');
redirect('siswa.php');
