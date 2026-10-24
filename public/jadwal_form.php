<?php
require __DIR__ . '/../src/bootstrap.php';
require_login();

$kelasId = to_id(query('kelas_id'));
$kelas = $kelasId !== null ? db_one('SELECT kelas_id, kelas_nama FROM kelas WHERE kelas_id = ?', [$kelasId]) : null;
if ($kelas === null) {
    flash('warning', 'Data kelas tidak ditemukan.');
    redirect('jadwal.php');
}

$jadwal = [];
foreach (db_all('SELECT jadwal_hari, jadwal_masuk, jadwal_pulang FROM jadwal WHERE kelas_id = ?', [$kelasId]) as $row) {
    $jadwal[$row['jadwal_hari']] = $row;
}

$pageTitle = 'Edit Jadwal ' . $kelas['kelas_nama'];
$activeMenu = 'jadwal';
$breadcrumbs = ['Data Jadwal' => 'jadwal.php', $pageTitle => null];
require APP_ROOT . '/src/views/header.php';
?>
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-edit mr-1"></i> <?= e($pageTitle) ?>
    </div>
    <div class="card-body">
        <p class="text-muted small">
            Kosongkan jam atau samakan jam masuk dan pulang untuk menandai hari libur.
            Presensi masuk dibuka <?= (int) config('attendance.early_checkin_minutes', 60) ?> menit sebelum jam masuk.
        </p>
        <form action="<?= e(url('actions/jadwal_simpan.php')) ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="kelas_id" value="<?= (int) $kelasId ?>">
            <div class="table-responsive-md">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Hari</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (HARI_JADWAL as $hari) : ?>
                            <?php
                            $j = isset($jadwal[$hari]) ? $jadwal[$hari] : ['jadwal_masuk' => '00:00:00', 'jadwal_pulang' => '00:00:00'];
                            $libur = $j['jadwal_masuk'] === $j['jadwal_pulang'];
                            $key = strtolower($hari);
                            ?>
                            <tr>
                                <th><?= e($hari) ?></th>
                                <td>
                                    <input type="time" class="form-control bg-white" name="jadwal[<?= e($key) ?>][masuk]"
                                        value="<?= e(old_jadwal($key, 'masuk', $libur ? '' : format_jam($j['jadwal_masuk']))) ?>">
                                </td>
                                <td>
                                    <input type="time" class="form-control bg-white" name="jadwal[<?= e($key) ?>][pulang]"
                                        value="<?= e(old_jadwal($key, 'pulang', $libur ? '' : format_jam($j['jadwal_pulang']))) ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?= e(url('jadwal.php')) ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>
<?php
/**
 * Nilai jam dari input lama (setelah gagal validasi), atau $default.
 */
function old_jadwal($hari, $jenis, $default)
{
    $old = old('jadwal', null);
    return isset($old[$hari][$jenis]) && is_string($old[$hari][$jenis]) ? $old[$hari][$jenis] : $default;
}

forget_input();
require APP_ROOT . '/src/views/footer.php';
