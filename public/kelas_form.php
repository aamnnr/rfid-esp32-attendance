<?php
require __DIR__ . '/../src/bootstrap.php';
require_login();

$id = to_id(query('id'));
$kelas = ['kelas_id' => null, 'kelas_nama' => ''];

if ($id !== null) {
    $kelas = db_one('SELECT kelas_id, kelas_nama FROM kelas WHERE kelas_id = ?', [$id]);
    if ($kelas === null) {
        flash('warning', 'Data kelas tidak ditemukan.');
        redirect('kelas.php');
    }
}

$isEdit = $id !== null;
$pageTitle = $isEdit ? 'Edit Data Kelas' : 'Tambah Data Kelas';
$activeMenu = 'kelas';
$breadcrumbs = ['Data Kelas' => 'kelas.php', $pageTitle => null];
require APP_ROOT . '/src/views/header.php';
?>
<div class="card mb-4">
    <div class="card-header">
        <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus-square' ?> mr-1"></i> <?= e($pageTitle) ?>
    </div>
    <div class="card-body">
        <form action="<?= e(url('actions/kelas_simpan.php')) ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($kelas['kelas_id']) ?>">
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="kelasNama">Nama Kelas</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" id="kelasNama" name="kelasNama" placeholder="Contoh: X TKRO 1"
                        minlength="2" maxlength="50" autocomplete="off" required autofocus
                        value="<?= e(old('kelasNama', $kelas['kelas_nama'])) ?>">
                    <?php if (!$isEdit) : ?>
                        <small class="form-text text-muted">
                            Jadwal Senin-Minggu dibuat otomatis (libur). Atur jamnya di menu Data Jadwal.
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-8 ml-auto">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="<?= e(url('kelas.php')) ?>" class="btn btn-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php
forget_input();
require APP_ROOT . '/src/views/footer.php';
