<?php
require __DIR__ . '/../src/bootstrap.php';
require_login();

$id = to_id(query('id'));
$siswa = [
    'siswa_id' => null, 'siswa_rfid' => '', 'siswa_absen' => '', 'siswa_nama' => '', 'kelas_id' => '',
    'siswa_jeniskelamin' => '', 'siswa_lahir' => '', 'siswa_nomorhp' => '', 'siswa_alamat' => '', 'siswa_foto' => null,
];

if ($id !== null) {
    $siswa = db_one('SELECT * FROM siswa WHERE siswa_id = ?', [$id]);
    if ($siswa === null) {
        flash('warning', 'Data siswa tidak ditemukan.');
        redirect('siswa.php');
    }
}

// Nilai form: input lama (setelah gagal validasi) diutamakan dari data DB.
$field = function ($name, $column) use ($siswa) {
    return old($name, (string) $siswa[$column]);
};

$kelasList = db_all('SELECT kelas_id, kelas_nama FROM kelas ORDER BY kelas_nama');
$isEdit = $id !== null;

$pageTitle = $isEdit ? 'Edit Data Siswa' : 'Tambah Data Siswa';
$activeMenu = 'siswa';
$breadcrumbs = ['Data Siswa' => 'siswa.php', $pageTitle => null];
require APP_ROOT . '/src/views/header.php';
?>
<div class="card mb-4">
    <div class="card-header">
        <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus-square' ?> mr-1"></i>
        <?= e($isEdit ? 'Edit: ' . $siswa['siswa_nama'] : 'Tambah Data Siswa') ?>
    </div>
    <div class="card-body">
        <?php if (!$kelasList) : ?>
            <div class="alert alert-warning">
                Belum ada data kelas. <a href="<?= e(url('kelas_form.php')) ?>">Tambah kelas</a> terlebih dahulu.
            </div>
        <?php endif; ?>
        <form action="<?= e(url('actions/siswa_simpan.php')) ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($siswa['siswa_id']) ?>">

            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="siswaRfid">Kode Kartu RFID</label>
                <div class="col-sm-8">
                    <div class="input-group">
                        <input type="text" class="form-control text-uppercase" id="siswaRfid" name="siswaRfid"
                            placeholder="Tap kartu pada reader lalu klik Ambil" maxlength="32"
                            pattern="[A-Za-z0-9]{4,32}" autocomplete="off" required value="<?= e($field('siswaRfid', 'siswa_rfid')) ?>">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button"
                                data-rfid-fetch="<?= e(url('api/rfid_terakhir.php')) ?>"
                                data-target="#siswaRfid" data-help="#siswaRfidHelp">
                                <i class="fas fa-wifi"></i> Ambil dari reader
                            </button>
                        </div>
                    </div>
                    <small id="siswaRfidHelp" class="form-text text-muted">
                        Kartu yang belum terdaftar dan di-tap pada reader akan muncul di sini.
                    </small>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="siswaAbsen">Nomor Absen</label>
                <div class="col-sm-8">
                    <input type="text" inputmode="numeric" class="form-control" id="siswaAbsen" name="siswaAbsen"
                        placeholder="Masukkan nomor absen" maxlength="16" pattern="\d{1,16}" autocomplete="off" required
                        value="<?= e($field('siswaAbsen', 'siswa_absen')) ?>">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="siswaNama">Nama Lengkap</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" id="siswaNama" name="siswaNama"
                        placeholder="Masukkan nama lengkap" minlength="2" maxlength="50" autocomplete="off" required
                        value="<?= e($field('siswaNama', 'siswa_nama')) ?>">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="siswaKelas">Kelas</label>
                <div class="col-sm-8">
                    <?php $kelasTerpilih = $field('siswaKelas', 'kelas_id'); ?>
                    <select class="custom-select" id="siswaKelas" name="siswaKelas" required>
                        <option value="">- Pilih Kelas -</option>
                        <?php foreach ($kelasList as $kelas) : ?>
                            <option value="<?= (int) $kelas['kelas_id'] ?>" <?= (string) $kelas['kelas_id'] === $kelasTerpilih ? 'selected' : '' ?>>
                                <?= e($kelas['kelas_nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="siswaJK">Jenis Kelamin</label>
                <div class="col-sm-8">
                    <?php $jk = $field('siswaJK', 'siswa_jeniskelamin'); ?>
                    <select class="custom-select" id="siswaJK" name="siswaJK" required>
                        <option value="">- Pilih Jenis Kelamin -</option>
                        <?php foreach (JENIS_KELAMIN as $pilihan) : ?>
                            <option value="<?= e($pilihan) ?>" <?= $jk === $pilihan ? 'selected' : '' ?>><?= e($pilihan) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="siswaTgl">Tanggal Lahir</label>
                <div class="col-sm-8">
                    <input type="date" class="form-control" id="siswaTgl" name="siswaTgl" required
                        max="<?= e(date('Y-m-d')) ?>" value="<?= e($field('siswaTgl', 'siswa_lahir')) ?>">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="siswaNohp">
                    Nomor Telepon <small class="text-danger">Contoh: 62822xxxx4496</small>
                </label>
                <div class="col-sm-8">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text">+</div>
                        </div>
                        <input type="tel" class="form-control" id="siswaNohp" name="siswaNohp"
                            placeholder="Masukkan nomor telepon" pattern="\d{6,19}" autocomplete="off" required
                            value="<?= e($field('siswaNohp', 'siswa_nomorhp')) ?>">
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="siswaAlamat">Alamat Lengkap</label>
                <div class="col-sm-8">
                    <textarea class="form-control" id="siswaAlamat" name="siswaAlamat" placeholder="Masukkan alamat lengkap"
                        minlength="4" maxlength="500" required><?= e($field('siswaAlamat', 'siswa_alamat')) ?></textarea>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="siswaFoto">Foto Siswa <small class="text-muted">(opsional)</small></label>
                <div class="col-sm-8">
                    <?php if (!empty($siswa['siswa_foto'])) : ?>
                        <img src="<?= e(upload_url('siswa', $siswa['siswa_foto'])) ?>" alt="Foto siswa" class="img-thumbnail mb-2" width="120">
                    <?php endif; ?>
                    <input type="file" class="form-control-file" id="siswaFoto" name="siswaFoto" accept="image/jpeg,image/png">
                    <small class="form-text text-muted">JPG/PNG, maksimal <?= (int) (config('upload.max_bytes') / 1024 / 1024) ?> MB.</small>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-8 ml-auto">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="<?= e(url('siswa.php')) ?>" class="btn btn-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php
forget_input();
require APP_ROOT . '/src/views/footer.php';
