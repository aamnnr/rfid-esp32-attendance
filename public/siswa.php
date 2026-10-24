<?php
require __DIR__ . '/../src/bootstrap.php';
require_login();

$rows = db_all(
    'SELECT s.siswa_id, s.siswa_rfid, s.siswa_nama, s.siswa_absen, s.siswa_jeniskelamin, s.siswa_lahir, k.kelas_nama
     FROM siswa s
     INNER JOIN kelas k ON s.kelas_id = k.kelas_id
     ORDER BY k.kelas_nama, s.siswa_nama'
);

$pageTitle = 'Data Siswa';
$activeMenu = 'siswa';
require APP_ROOT . '/src/views/header.php';
?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div><i class="fas fa-table mr-1"></i> Data Siswa</div>
        <a href="<?= e(url('siswa_form.php')) ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> Tambah Siswa
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered js-datatable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>No. Absen</th>
                        <th>RFID</th>
                        <th>Jenis Kelamin</th>
                        <th>Tanggal Lahir</th>
                        <th>Kelas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td><?= e($row['siswa_nama']) ?></td>
                            <td><?= e($row['siswa_absen']) ?></td>
                            <td><code><?= e($row['siswa_rfid']) ?></code></td>
                            <td><?= e($row['siswa_jeniskelamin']) ?></td>
                            <td data-order="<?= e($row['siswa_lahir']) ?>"><?= e(format_tanggal($row['siswa_lahir'])) ?></td>
                            <td><?= e($row['kelas_nama']) ?></td>
                            <td class="text-nowrap">
                                <a href="<?= e(url('siswa_form.php', ['id' => $row['siswa_id']])) ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="<?= e(url('actions/siswa_hapus.php')) ?>" method="POST" class="d-inline"
                                    data-confirm="Hapus siswa <?= e($row['siswa_nama']) ?> beserta seluruh rekap presensinya?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $row['siswa_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require APP_ROOT . '/src/views/footer.php'; ?>
