<?php
require __DIR__ . '/../src/bootstrap.php';
require_login();

$kelasList = db_all('SELECT kelas_id, kelas_nama FROM kelas ORDER BY kelas_nama');

// Kelompokkan jadwal per kelas: [kelas_id][hari] => row
$jadwal = [];
foreach (db_all('SELECT kelas_id, jadwal_hari, jadwal_masuk, jadwal_pulang FROM jadwal') as $row) {
    $jadwal[$row['kelas_id']][$row['jadwal_hari']] = $row;
}

$pageTitle = 'Data Jadwal';
$activeMenu = 'jadwal';
require APP_ROOT . '/src/views/header.php';
?>
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table mr-1"></i> Jadwal Masuk &amp; Pulang per Kelas
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <?php foreach (HARI_JADWAL as $hari) : ?>
                            <th class="text-center"><?= e($hari) ?></th>
                        <?php endforeach; ?>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kelasList as $kelas) : ?>
                        <tr>
                            <td><?= e($kelas['kelas_nama']) ?></td>
                            <?php foreach (HARI_JADWAL as $hari) : ?>
                                <?php $j = isset($jadwal[$kelas['kelas_id']][$hari]) ? $jadwal[$kelas['kelas_id']][$hari] : null; ?>
                                <td class="text-center small">
                                    <?php if ($j === null || $j['jadwal_masuk'] === $j['jadwal_pulang']) : ?>
                                        <span class="text-muted">Libur</span>
                                    <?php else : ?>
                                        <?= e(format_jam($j['jadwal_masuk'])) ?>&ndash;<?= e(format_jam($j['jadwal_pulang'])) ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <a href="<?= e(url('jadwal_form.php', ['kelas_id' => $kelas['kelas_id']])) ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$kelasList) : ?>
                        <tr>
                            <td colspan="<?= count(HARI_JADWAL) + 2 ?>" class="text-center text-muted">Belum ada data kelas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require APP_ROOT . '/src/views/footer.php'; ?>
