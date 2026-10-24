<?php
require __DIR__ . '/../src/bootstrap.php';
require_login();

$rows = db_all(
    'SELECT k.kelas_id, k.kelas_nama, COUNT(s.siswa_id) AS jumlah_siswa
     FROM kelas k
     LEFT JOIN siswa s ON s.kelas_id = k.kelas_id
     GROUP BY k.kelas_id, k.kelas_nama
     ORDER BY k.kelas_nama'
);

$pageTitle = 'Data Kelas';
$activeMenu = 'kelas';
require APP_ROOT . '/src/views/header.php';
?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div><i class="fas fa-table mr-1"></i> Data Kelas</div>
        <a href="<?= e(url('kelas_form.php')) ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> Tambah Kelas
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered js-datatable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Nama Kelas</th>
                        <th>Jumlah Siswa</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td><?= e($row['kelas_nama']) ?></td>
                            <td><?= (int) $row['jumlah_siswa'] ?></td>
                            <td class="text-nowrap">
                                <a href="<?= e(url('kelas_form.php', ['id' => $row['kelas_id']])) ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="<?= e(url('jadwal_form.php', ['kelas_id' => $row['kelas_id']])) ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-calendar-alt"></i> Jadwal
                                </a>
                                <form action="<?= e(url('actions/kelas_hapus.php')) ?>" method="POST" class="d-inline"
                                    data-confirm="Hapus kelas <?= e($row['kelas_nama']) ?> beserta jadwalnya?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $row['kelas_id'] ?>">
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
