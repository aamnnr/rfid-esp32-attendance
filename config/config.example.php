<?php
/**
 * Contoh konfigurasi aplikasi.
 *
 * Salin file ini menjadi `config/config.php`, lalu sesuaikan nilainya.
 * `config/config.php` sengaja tidak ikut di-commit (lihat .gitignore)
 * karena berisi kredensial.
 */
return [
    // Nama aplikasi yang tampil di judul halaman dan kiosk presensi.
    'app_name' => 'Sistem Presensi RFID',

    'timezone' => 'Asia/Jakarta',

    // true hanya saat pengembangan: menampilkan detail error di browser.
    'debug' => false,

    // URL dasar folder `public/` tanpa garis miring di akhir.
    // Kosongkan untuk deteksi otomatis. Contoh: '/rfid-esp32-attendance/public'
    'base_url' => '',

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'presensi_rfid',
        'user' => 'root',
        'pass' => '',
    ],

    // Kunci rahasia yang dikirim perangkat ESP8266/ESP32-CAM melalui header
    // `X-Device-Key`. Harus sama dengan DEVICE_KEY di firmware/*/secrets.h.
    // Kosongkan untuk menonaktifkan pengecekan (tidak disarankan di produksi).
    'device_key' => '',

    'attendance' => [
        // Berapa menit sebelum jam masuk presensi masuk sudah dibuka.
        'early_checkin_minutes' => 60,
        // Batas waktu (detik) foto dari ESP32-CAM dipasangkan ke tap kartu.
        'photo_match_seconds' => 120,
    ],

    'upload' => [
        // Ukuran maksimal foto (byte).
        'max_bytes' => 5 * 1024 * 1024,
    ],
];
