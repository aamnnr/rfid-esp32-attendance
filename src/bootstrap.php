<?php
/**
 * Titik masuk bersama untuk semua halaman dan endpoint.
 * Memuat konfigurasi, mengatur penanganan error, dan memuat helper.
 */

define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_PATH', APP_ROOT . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

$configFile = APP_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Konfigurasi belum dibuat. Salin config/config.example.php menjadi config/config.php.');
}
$GLOBALS['app_config'] = require $configFile;

/**
 * Ambil nilai konfigurasi dengan notasi titik, mis. config('db.host').
 */
function config($key, $default = null)
{
    $value = $GLOBALS['app_config'];
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

error_reporting(E_ALL);
ini_set('display_errors', config('debug') ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set(config('timezone', 'Asia/Jakarta'));

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/attendance.php';

set_exception_handler(function ($e) {
    error_log((string) $e);
    $message = config('debug') ? $e->getMessage() : 'Terjadi kesalahan pada server.';

    if (!headers_sent()) {
        http_response_code(500);
    }
    switch (response_format()) {
        case 'json':
            json_response(['status' => false, 'message' => $message], 500);
            break;
        case 'text':
            text_response('Server Error|Coba Lagi', 500);
            break;
        default:
            echo '<h1>500</h1><p>' . e($message) . '</p>';
    }
});
