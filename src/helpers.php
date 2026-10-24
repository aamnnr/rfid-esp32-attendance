<?php
/**
 * Helper umum: escaping, URL, respons, flash message, CSRF, validasi,
 * format tanggal Indonesia, dan penyimpanan foto.
 */

// ---------------------------------------------------------------------------
// Output & URL
// ---------------------------------------------------------------------------

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * URL dasar folder public/, mis. '/rfid-esp32-attendance/public' atau ''.
 */
function base_url()
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $configured = (string) config('base_url', '');
    if ($configured !== '') {
        return $base = rtrim($configured, '/');
    }

    // Deteksi otomatis: potong path skrip relatif terhadap folder public/.
    $script = str_replace('\\', '/', isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
    $file = realpath(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '');
    $public = realpath(PUBLIC_PATH);
    if ($file && $public && strpos($file, $public) === 0) {
        $relative = str_replace('\\', '/', substr($file, strlen($public)));
        if ($relative !== '' && substr($script, -strlen($relative)) === $relative) {
            return $base = rtrim(substr($script, 0, -strlen($relative)), '/');
        }
    }

    return $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
}

function url($path = '', array $query = [])
{
    $url = base_url() . '/' . ltrim($path, '/');
    if ($query) {
        $url .= '?' . http_build_query($query);
    }
    return $url;
}

function asset($path)
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect($path, array $query = [])
{
    header('Location: ' . url($path, $query));
    exit;
}

// ---------------------------------------------------------------------------
// Respons
// ---------------------------------------------------------------------------

/**
 * Format respons error untuk exception handler: 'html' (default), 'json', 'text'.
 */
function response_format($format = null)
{
    static $current = 'html';
    if ($format !== null) {
        $current = $format;
    }
    return $current;
}

function json_response(array $data, $status = 200)
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function text_response($text, $status = 200)
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo $text;
    exit;
}

function require_method($method)
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        header('Allow: ' . $method);
        if (response_format() === 'json') {
            json_response(['status' => false, 'message' => 'Metode request tidak diizinkan.'], 405);
        }
        text_response('Metode request tidak diizinkan.', 405);
    }
}

// ---------------------------------------------------------------------------
// Input
// ---------------------------------------------------------------------------

function input($key, $default = '')
{
    if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
        return $default;
    }
    return trim($_POST[$key]);
}

function query($key, $default = '')
{
    if (!isset($_GET[$key]) || !is_string($_GET[$key])) {
        return $default;
    }
    return trim($_GET[$key]);
}

/**
 * Ambil bilangan bulat positif dari string, atau null jika tidak valid.
 */
function to_id($value)
{
    if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
        return (int) $value;
    }
    return null;
}

// ---------------------------------------------------------------------------
// Flash message & input lama (untuk pola Post/Redirect/Get)
// ---------------------------------------------------------------------------

function flash($type, $message)
{
    start_session();
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes()
{
    start_session();
    $messages = isset($_SESSION['_flash']) ? $_SESSION['_flash'] : [];
    unset($_SESSION['_flash']);
    return $messages;
}

function remember_input()
{
    start_session();
    $data = $_POST;
    unset($data['_token']);
    $_SESSION['_old'] = $data;
}

function old($key, $default = '')
{
    start_session();
    return isset($_SESSION['_old'][$key]) ? $_SESSION['_old'][$key] : $default;
}

function forget_input()
{
    start_session();
    unset($_SESSION['_old']);
}

/**
 * Simpan input, tampilkan pesan error, lalu kembali ke halaman form.
 */
function back_with_error($message, $path, array $query = [])
{
    remember_input();
    flash('danger', $message);
    redirect($path, $query);
}

// ---------------------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------------------

function csrf_token()
{
    start_session();
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf()
{
    $token = isset($_POST['_token']) && is_string($_POST['_token']) ? $_POST['_token'] : '';
    if ($token !== '' && hash_equals(csrf_token(), $token)) {
        return;
    }

    if (response_format() === 'json') {
        json_response(['status' => false, 'message' => 'Sesi formulir kedaluwarsa. Muat ulang halaman.'], 419);
    }
    flash('danger', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
    redirect('index.php');
}

// ---------------------------------------------------------------------------
// Validasi
// ---------------------------------------------------------------------------

const JENIS_KELAMIN = ['Laki-laki', 'Perempuan'];

function valid_date($value)
{
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

/**
 * Normalisasi jam 'HH:MM' atau 'HH:MM:SS' menjadi 'HH:MM:SS', null jika tidak valid.
 */
function normalize_time($value)
{
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $value, $m)) {
        return null;
    }
    return sprintf('%s:%s:%s', $m[1], $m[2], isset($m[3]) ? $m[3] : '00');
}

/**
 * UID kartu RFID: hanya huruf/angka (heksadesimal dari reader), huruf besar.
 * Tanda kutip/spasi dari firmware lama otomatis dibuang.
 */
function normalize_rfid($value)
{
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
}

function valid_rfid($value)
{
    return (bool) preg_match('/^[A-Z0-9]{4,32}$/', $value);
}

function valid_phone($value)
{
    return (bool) preg_match('/^\d{6,19}$/', $value);
}

function str_length($value)
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

// ---------------------------------------------------------------------------
// Tanggal (Bahasa Indonesia)
// ---------------------------------------------------------------------------

const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

const BULAN = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

function to_datetime($value)
{
    if ($value instanceof DateTimeInterface) {
        return $value;
    }
    return new DateTimeImmutable($value === null ? 'now' : (string) $value);
}

/**
 * Nama hari sesuai enum `jadwal_hari`, mis. 'Senin'.
 */
function nama_hari($date = null)
{
    return HARI[(int) to_datetime($date)->format('w')];
}

function nama_bulan($month)
{
    return array_key_exists((int) $month, BULAN) ? BULAN[(int) $month] : '';
}

/**
 * '2024-08-17' => '17 Agustus 2024' atau 'Sabtu, 17 Agustus 2024'.
 */
function format_tanggal($date, $withDay = false)
{
    if (empty($date)) {
        return '-';
    }
    $dt = to_datetime($date);
    $text = $dt->format('j') . ' ' . nama_bulan($dt->format('n')) . ' ' . $dt->format('Y');
    return $withDay ? nama_hari($dt) . ', ' . $text : $text;
}

function format_jam($time)
{
    return empty($time) ? '-' : substr($time, 0, 5);
}

// ---------------------------------------------------------------------------
// Foto
// ---------------------------------------------------------------------------

const IMAGE_EXTENSIONS = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];

/**
 * Validasi isi gambar (JPEG/PNG) lalu simpan ke public/uploads/{folder}.
 * Mengembalikan nama file, atau null jika gambar tidak valid.
 */
function store_image_bytes($bytes, $folder)
{
    if ($bytes === '' || strlen($bytes) > (int) config('upload.max_bytes', 5 * 1024 * 1024)) {
        return null;
    }

    $info = @getimagesizefromstring($bytes);
    if ($info === false || !array_key_exists($info[2], IMAGE_EXTENSIONS)) {
        return null;
    }

    $dir = UPLOAD_PATH . '/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException("Gagal membuat folder upload: $dir");
    }

    $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . IMAGE_EXTENSIONS[$info[2]];
    if (file_put_contents($dir . '/' . $filename, $bytes, LOCK_EX) === false) {
        throw new RuntimeException("Gagal menyimpan foto ke $dir");
    }

    return $filename;
}

/**
 * Simpan file dari $_FILES. Mengembalikan nama file, atau null jika tidak valid.
 */
function store_uploaded_image(array $file, $folder)
{
    if (!isset($file['error'], $file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK
        || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    return store_image_bytes((string) file_get_contents($file['tmp_name']), $folder);
}

function has_uploaded_file($key)
{
    return isset($_FILES[$key]['error']) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE;
}

function delete_upload($folder, $filename)
{
    if (empty($filename)) {
        return;
    }
    $path = UPLOAD_PATH . '/' . $folder . '/' . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

function upload_url($folder, $filename)
{
    return empty($filename) ? null : url('uploads/' . $folder . '/' . rawurlencode(basename($filename)));
}
