<?php
/**
 * Sesi dan autentikasi admin.
 */

function start_session()
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    session_name('presensi_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_url() === '' ? '/' : base_url() . '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function current_admin()
{
    start_session();
    return isset($_SESSION['admin']) ? $_SESSION['admin'] : null;
}

/**
 * Halaman admin: alihkan ke login jika belum masuk.
 */
function require_login()
{
    if (current_admin() !== null) {
        return;
    }
    if (response_format() === 'json') {
        json_response(['status' => false, 'message' => 'Sesi Anda berakhir, silakan login ulang.'], 401);
    }
    redirect('login.php');
}

/**
 * Verifikasi username/password. Password lama yang masih tersimpan
 * sebagai teks biasa diterima sekali lalu otomatis di-hash ulang.
 */
function attempt_login($username, $password)
{
    $admin = db_one(
        'SELECT admin_id, admin_username, admin_password, admin_nama FROM admin WHERE admin_username = ?',
        [$username]
    );
    if ($admin === null) {
        // Samakan waktu respons agar username tidak bisa ditebak dari durasi.
        password_verify($password, '$2y$12$VN.vnOTGqhn1QZYaoWWzFOgymKdLfvpLRmMMh.lPXQo45LWgmiS1i');
        return false;
    }

    $stored = $admin['admin_password'];
    $isHashed = !empty(password_get_info($stored)['algo']);

    if ($isHashed) {
        if (!password_verify($password, $stored)) {
            return false;
        }
        $needsRehash = password_needs_rehash($stored, PASSWORD_DEFAULT);
    } else {
        if (!hash_equals($stored, $password)) {
            return false;
        }
        $needsRehash = true;
    }

    if ($needsRehash) {
        // Gagal menyimpan hash (mis. kolom lama masih VARCHAR(50)) tidak boleh menggagalkan login.
        try {
            db_exec('UPDATE admin SET admin_password = ? WHERE admin_id = ?', [
                password_hash($password, PASSWORD_DEFAULT),
                (int) $admin['admin_id'],
            ]);
        } catch (mysqli_sql_exception $e) {
            error_log('Gagal meng-hash ulang password admin: ' . $e->getMessage()
                . '. Jalankan database/migrations/001_upgrade_legacy.sql.');
        }
    }

    start_session();
    session_regenerate_id(true);
    $_SESSION['admin'] = [
        'id'       => (int) $admin['admin_id'],
        'username' => $admin['admin_username'],
        'nama'     => $admin['admin_nama'],
    ];

    return true;
}

function logout()
{
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Endpoint perangkat (ESP8266/ESP32-CAM) wajib mengirim header X-Device-Key
 * yang cocok dengan config 'device_key' (jika diisi).
 */
function require_device_key()
{
    $expected = (string) config('device_key', '');
    if ($expected === '') {
        return;
    }

    $given = '';
    if (isset($_SERVER['HTTP_X_DEVICE_KEY'])) {
        $given = (string) $_SERVER['HTTP_X_DEVICE_KEY'];
    } elseif (isset($_REQUEST['key']) && is_string($_REQUEST['key'])) {
        $given = $_REQUEST['key'];
    }

    if (!hash_equals($expected, $given)) {
        text_response('Akses Ditolak|Kunci Salah', 401);
    }
}
