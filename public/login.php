<?php
require __DIR__ . '/../src/bootstrap.php';

if (current_admin() !== null) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = input('username');
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        back_with_error('Silakan isi username dan password.', 'login.php');
    }
    if (!attempt_login($username, $password)) {
        back_with_error('Username atau password salah.', 'login.php');
    }

    forget_input();
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login - <?= e(config('app_name')) ?></title>
    <link href="<?= e(asset('css/bootstrap.css')) ?>" rel="stylesheet">
</head>

<body class="bg-primary">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header">
                                    <h3 class="text-center font-weight-light my-4">Login</h3>
                                </div>
                                <div class="card-body">
                                    <?php require APP_ROOT . '/src/views/flash.php'; ?>
                                    <form action="<?= e(url('login.php')) ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <div class="form-group">
                                            <label class="small mb-1" for="inputUsername">Username</label>
                                            <input class="form-control py-4" id="inputUsername" name="username" type="text"
                                                placeholder="Masukkan username" value="<?= e(old('username')) ?>"
                                                autocomplete="username" required autofocus>
                                        </div>
                                        <div class="form-group">
                                            <label class="small mb-1" for="inputPassword">Password</label>
                                            <input class="form-control py-4" id="inputPassword" name="password" type="password"
                                                placeholder="Masukkan password" autocomplete="current-password" required>
                                        </div>
                                        <button class="btn btn-primary btn-block" type="submit">Login</button>
                                    </form>
                                </div>
                                <div class="card-footer text-center">
                                    <div class="small"><a href="<?= e(url('absen.php')) ?>">Ke halaman presensi</a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="<?= e(asset('js/jquery-3.5.1.min.js')) ?>"></script>
    <script src="<?= e(asset('js/bootstrap.bundle.min.js')) ?>"></script>
</body>

</html>
<?php forget_input(); ?>
