<?php
require __DIR__ . '/../src/bootstrap.php';
require_login();

// Terima ?bulan=YYYY-MM (atau ?tanggal=YYYY-MM-DD dari tautan lama).
$bulan = query('bulan', substr(query('tanggal'), 0, 7));
if (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $bulan, $m)) {
    flash('danger', 'Pilih bulan laporan terlebih dahulu.');
    redirect('rekap.php');
}
$tahun = (int) $m[1];
$bulanAngka = (int) $m[2];
$jumlahHari = (int) date('t', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
$awal = sprintf('%04d-%02d-01', $tahun, $bulanAngka);
$akhir = sprintf('%04d-%02d-%02d', $tahun, $bulanAngka, $jumlahHari);

$kelasId = to_id(query('kelas_id'));
$kelas = $kelasId !== null ? db_one('SELECT kelas_nama FROM kelas WHERE kelas_id = ?', [$kelasId]) : null;
if ($kelasId !== null && $kelas === null) {
    flash('warning', 'Data kelas tidak ditemukan.');
    redirect('rekap.php');
}

$kelasFilter = $kelasId !== null ? 'WHERE s.kelas_id = ?' : '';
$kelasParams = $kelasId !== null ? [$kelasId] : [];

$siswaList = db_all(
    "SELECT s.siswa_id, s.siswa_nama, k.kelas_nama
     FROM siswa s INNER JOIN kelas k ON s.kelas_id = k.kelas_id
     $kelasFilter
     ORDER BY k.kelas_nama, s.siswa_nama",
    $kelasParams
);

// [siswa_id][tanggal] => keterangan
$kehadiran = [];
$rows = db_all(
    "SELECT r.siswa_id, DAY(r.rekap_tanggal) AS hari, r.rekap_keterangan
     FROM rekap r INNER JOIN siswa s ON r.siswa_id = s.siswa_id
     " . ($kelasFilter ? "$kelasFilter AND" : 'WHERE') . " r.rekap_tanggal BETWEEN ? AND ?",
    array_merge($kelasParams, [$awal, $akhir])
);
foreach ($rows as $row) {
    $kehadiran[$row['siswa_id']][(int) $row['hari']] = $row['rekap_keterangan'];
}

$kode = [
    KET_MASUK     => ['H.M', 'status-masuk'],
    KET_PULANG    => ['H.P', 'status-pulang'],
    KET_TERLAMBAT => ['H.T', 'status-terlambat'],
    KET_ALPA      => ['A', 'status-alpa'],
];

$judulKelas = $kelas ? $kelas['kelas_nama'] : 'Semua Kelas';
$judulBulan = nama_bulan($bulanAngka) . ' ' . $tahun;
$namaFile = preg_replace('/[^A-Za-z0-9]+/', '-', "rekap $judulKelas $bulan");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Laporan <?= e($judulKelas . ' ' . $judulBulan) ?> - <?= e(config('app_name')) ?></title>
    <link href="<?= e(asset('css/bootstrap.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/laporan.css')) ?>" rel="stylesheet">
</head>

<body>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Rekap Presensi <?= e($judulKelas) ?> &ndash; <?= e($judulBulan) ?></h1>
            <div class="d-print-none">
                <button type="button" id="exportButton" class="btn btn-success btn-sm" data-filename="<?= e($namaFile) ?>.xlsx">
                    Export Excel
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">Cetak</button>
            </div>
        </div>
        <p class="small text-muted">
            Keterangan: H.M = Hadir Masuk, H.P = Hadir Pulang, H.T = Hadir Terlambat, A = Alpa, - = Tidak ada data.
        </p>
        <div class="table-responsive">
            <table id="laporanTable" class="table table-sm table-bordered laporan-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th class="kolom-nama">Nama Siswa</th>
                        <?php if (!$kelas) : ?>
                            <th>Kelas</th>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $jumlahHari; $i++) : ?>
                            <th class="kolom-hari"><?= $i ?></th>
                        <?php endfor; ?>
                        <th>Hadir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($siswaList as $no => $siswa) : ?>
                        <?php $hadir = 0; ?>
                        <tr>
                            <td class="text-center"><?= $no + 1 ?></td>
                            <td><?= e($siswa['siswa_nama']) ?></td>
                            <?php if (!$kelas) : ?>
                                <td><?= e($siswa['kelas_nama']) ?></td>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $jumlahHari; $i++) : ?>
                                <?php
                                $ket = isset($kehadiran[$siswa['siswa_id']][$i]) ? $kehadiran[$siswa['siswa_id']][$i] : null;
                                if ($ket !== null && $ket !== KET_ALPA) {
                                    $hadir++;
                                }
                                list($label, $class) = $ket !== null && isset($kode[$ket]) ? $kode[$ket] : ['-', 'status-kosong'];
                                ?>
                                <td class="kolom-hari <?= e($class) ?>"><?= e($label) ?></td>
                            <?php endfor; ?>
                            <td class="text-center font-weight-bold"><?= $hadir ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$siswaList) : ?>
                        <tr>
                            <td colspan="<?= $jumlahHari + ($kelas ? 3 : 4) ?>" class="text-center text-muted">Belum ada data siswa.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.getElementById('exportButton').addEventListener('click', function () {
            var workbook = XLSX.utils.table_to_book(document.getElementById('laporanTable'), { sheet: 'Rekap' });
            XLSX.writeFile(workbook, this.dataset.filename);
        });
    </script>
</body>

</html>
