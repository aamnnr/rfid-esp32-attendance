<?php
require __DIR__ . '/../src/bootstrap.php';
require_login();

$bulan = query('bulan', date('Y-m'));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
    $bulan = date('Y-m');
}
$awal = $bulan . '-01';
$akhir = date('Y-m-t', strtotime($awal));

$rows = db_all(
    'SELECT r.rekap_id, r.rekap_tanggal, r.rekap_masuk, r.rekap_keluar, r.rekap_keterangan,
            s.siswa_nama, s.siswa_absen, k.kelas_nama
     FROM rekap r
     INNER JOIN siswa s ON r.siswa_id = s.siswa_id
     INNER JOIN kelas k ON s.kelas_id = k.kelas_id
     WHERE r.rekap_tanggal BETWEEN ? AND ?
     ORDER BY r.rekap_tanggal DESC, r.rekap_masuk DESC',
    [$awal, $akhir]
);
$kelasList = db_all('SELECT kelas_id, kelas_nama FROM kelas ORDER BY kelas_nama');

$pageTitle = 'Data Rekap';
$activeMenu = 'rekap';
require APP_ROOT . '/src/views/header.php';
?>
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-file-excel mr-1"></i> Laporan Bulanan</div>
    <div class="card-body">
        <form action="<?= e(url('laporan.php')) ?>" method="GET" target="_blank" class="form-inline">
            <label class="sr-only" for="laporanKelas">Kelas</label>
            <select class="custom-select mr-2 mb-2" id="laporanKelas" name="kelas_id">
                <option value="">Semua kelas</option>
                <?php foreach ($kelasList as $kelas) : ?>
                    <option value="<?= (int) $kelas['kelas_id'] ?>"><?= e($kelas['kelas_nama']) ?></option>
                <?php endforeach; ?>
            </select>
            <label class="sr-only" for="laporanBulan">Bulan</label>
            <input type="month" class="form-control mr-2 mb-2" id="laporanBulan" name="bulan" value="<?= e($bulan) ?>"
                placeholder="YYYY-MM" pattern="\d{4}-\d{2}" required>
            <button type="submit" class="btn btn-success mb-2">Tampilkan Laporan</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <div><i class="fas fa-table mr-1"></i> Rekap <?= e(nama_bulan(substr($bulan, 5, 2)) . ' ' . substr($bulan, 0, 4)) ?></div>
        <form action="<?= e(url('rekap.php')) ?>" method="GET" class="form-inline">
            <label class="sr-only" for="filterBulan">Bulan</label>
            <input type="month" class="form-control form-control-sm mr-2" id="filterBulan" name="bulan" value="<?= e($bulan) ?>"
                placeholder="YYYY-MM" pattern="\d{4}-\d{2}">
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered js-datatable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama</th>
                        <th>No. Absen</th>
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
                            <td data-order="<?= e($row['rekap_tanggal']) ?>"><?= e(format_tanggal($row['rekap_tanggal'])) ?></td>
                            <td><?= e($row['siswa_nama']) ?></td>
                            <td><?= e($row['siswa_absen']) ?></td>
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
