<?php
/**
 * Layout admin bagian atas.
 *
 * @var string $pageTitle   Judul halaman
 * @var string $activeMenu  Kunci menu aktif (lihat $menu)
 * @var array  $breadcrumbs [label => url|null]
 */
$admin = current_admin();
$activeMenu = isset($activeMenu) ? $activeMenu : '';
$breadcrumbs = isset($breadcrumbs) ? $breadcrumbs : [$pageTitle => null];

$menu = [
    'dashboard' => ['Dashboard', 'index.php', 'fa-tachometer-alt'],
    'siswa'     => ['Data Siswa', 'siswa.php', 'fa-users'],
    'kelas'     => ['Data Kelas', 'kelas.php', 'fa-id-card'],
    'jadwal'    => ['Data Jadwal', 'jadwal.php', 'fa-calendar-alt'],
    'rekap'     => ['Data Rekap', 'rekap.php', 'fa-address-book'],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= e($pageTitle) ?> - <?= e(config('app_name')) ?></title>
    <link href="<?= e(asset('css/bootstrap.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/dataTables.bootstrap4.min.css')) ?>" rel="stylesheet">
    <script src="<?= e(asset('js/all.min.js')) ?>"></script>
</head>

<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand" href="<?= e(url('index.php')) ?>">
            <img src="<?= e(asset('images/logo.png')) ?>" alt="Logo" width="50" height="50">
        </a>
        <button class="btn btn-link btn-sm order-1 order-lg-0" id="sidebarToggle" type="button" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </button>
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Menu</div>
                        <?php foreach ($menu as $key => list($label, $href, $icon)) : ?>
                            <a class="nav-link<?= $activeMenu === $key ? ' active' : '' ?>" href="<?= e(url($href)) ?>">
                                <div class="sb-nav-link-icon"><i class="fas <?= e($icon) ?>"></i></div>
                                <?= e($label) ?>
                            </a>
                        <?php endforeach; ?>
                        <a class="nav-link" href="<?= e(url('absen.php')) ?>" target="_blank" rel="noopener">
                            <div class="sb-nav-link-icon"><i class="fas fa-camera"></i></div>
                            Kiosk Presensi
                        </a>
                        <a class="nav-link" href="<?= e(url('logout.php')) ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-sign-out-alt"></i></div>
                            Log out
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Masuk sebagai:</div>
                    <?= e($admin ? $admin['nama'] : '') ?>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid">
                    <h1 class="mt-4"><?= e($pageTitle) ?></h1>
                    <ol class="breadcrumb mb-4">
                        <?php foreach ($breadcrumbs as $label => $href) : ?>
                            <?php if ($href === null) : ?>
                                <li class="breadcrumb-item active"><?= e($label) ?></li>
                            <?php else : ?>
                                <li class="breadcrumb-item"><a href="<?= e(url($href)) ?>"><?= e($label) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                    <?php require __DIR__ . '/flash.php'; ?>
