<?php
require __DIR__ . '/../src/bootstrap.php';
require_login();

$id = to_id(query('id'));
$rekap = $id === null ? null : db_one(
    'SELECT r.*, s.siswa_nama, s.siswa_foto, k.kelas_nama
     FROM rekap r
     INNER JOIN siswa s ON r.siswa_id = s.siswa_id
     INNER JOIN kelas k ON s.kelas_id = k.kelas_id
     WHERE r.rekap_id = ?',
    [$id]
);
if ($rekap === null) {
    flash('warning', 'Data rekap tidak ditemukan.');
    redirect('rekap.php');
}

$photos = [
    'Foto Siswa'  => upload_url('siswa', $rekap['siswa_foto']),
    'Foto Masuk'  => upload_url('rekap', $rekap['rekap_photomasuk']),
    'Foto Pulang' => upload_url('rekap', $rekap['rekap_photokeluar']),
];

$pageTitle = 'Detail Rekap';
$activeMenu = 'rekap';
$breadcrumbs = ['Data Rekap' => 'rekap.php', $pageTitle => null];
require APP_ROOT . '/src/views/header.php';
?>
<div class="row">
    <?php foreach ($photos as $label => $src) : ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header small"><?= e($label) ?></div>
                <div class="card-body text-center">
                    <?php if ($src) : ?>
                        <a href="<?= e($src) ?>" target="_blank" rel="noopener">
                            <img src="<?= e($src) ?>" alt="<?= e($label) ?>" class="img-fluid rounded">
                        </a>
                    <?php else : ?>
                        <span class="text-muted">Tidak ada foto</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="fas fa-edit mr-1"></i> Edit Status Presensi</div>
    <div class="card-body">
        <form action="<?= e(url('actions/rekap_simpan.php')) ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $rekap['rekap_id'] ?>">
            <dl class="row">
                <dt class="col-sm-4">Nama</dt>
                <dd class="col-sm-8"><?= e($rekap['siswa_nama']) ?> (<?= e($rekap['kelas_nama']) ?>)</dd>
                <dt class="col-sm-4">Tanggal</dt>
                <dd class="col-sm-8"><?= e(format_tanggal($rekap['rekap_tanggal'], true)) ?></dd>
                <dt class="col-sm-4">Jam Masuk</dt>
                <dd class="col-sm-8"><?= e(format_jam($rekap['rekap_masuk'])) ?></dd>
                <dt class="col-sm-4">Jam Pulang</dt>
                <dd class="col-sm-8"><?= e(format_jam($rekap['rekap_keluar'])) ?></dd>
            </dl>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="rekapStatus">Status</label>
                <div class="col-sm-8">
                    <select class="custom-select" id="rekapStatus" name="rekapStatus" required>
                        <option value="">- Pilih Status -</option>
                        <?php foreach (REKAP_STATUSES as $status) : ?>
                            <option value="<?= e($status) ?>" <?= $rekap['rekap_keterangan'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-8 ml-auto">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="<?= e(url('rekap.php')) ?>" class="btn btn-secondary">Kembali</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php require APP_ROOT . '/src/views/footer.php'; ?>
