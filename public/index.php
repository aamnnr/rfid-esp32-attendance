<?php
require __DIR__ . '/../src/bootstrap.php';
require_login();

$today = date('Y-m-d');

$rows = db_all(
    'SELECT r.rekap_id, r.rekap_tanggal, r.rekap_masuk, r.rekap_keluar, r.rekap_keterangan,
            s.siswa_nama, k.kelas_nama
     FROM rekap r
     INNER JOIN siswa s ON r.siswa_id = s.siswa_id
     INNER JOIN kelas k ON s.kelas_id = k.kelas_id
     WHERE r.rekap_tanggal = ?
     ORDER BY r.rekap_masuk DESC',
    [$today]
);

$totalSiswa = (int) db_value('SELECT COUNT(*) FROM siswa WHERE siswa_status = 1');
$terlambat = count(array_filter($rows, function ($row) {
    return $row['rekap_keterangan'] === KET_TERLAMBAT;
}));

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
$breadcrumbs = ['Presensi ' . format_tanggal($today, true) => null];
require APP_ROOT . '/src/views/header.php';
?>
<div class="row">
    <div class="col-md-4">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <div class="small">Siswa aktif</div>
                <div class="h3 mb-0"><?= $totalSiswa ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <div class="small">Hadir hari ini</div>
                <div class="h3 mb-0"><?= count($rows) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <div class="small">Terlambat hari ini</div>
                <div class="h3 mb-0"><?= $terlambat ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table mr-1"></i>
        Rekap Presensi <small>Hari Ini</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered js-datatable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Masuk</th>
                        <th>Pulang</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td><?= e($row['siswa_nama']) ?></td>
                            <td><?= e($row['kelas_nama']) ?></td>
                            <td><?= e(format_jam($row['rekap_masuk'])) ?></td>
                            <td><?= e(format_jam($row['rekap_keluar'])) ?></td>
                            <td><?= e($row['rekap_keterangan']) ?></td>
                            <td>
                                <a href="<?= e(url('rekap_form.php', ['id' => $row['rekap_id']])) ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Lihat / Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require APP_ROOT . '/src/views/footer.php'; ?>
