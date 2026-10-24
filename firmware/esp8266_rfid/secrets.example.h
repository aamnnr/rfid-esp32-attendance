// Salin file ini menjadi `secrets.h` (satu folder dengan .ino) lalu isi nilainya.
// `secrets.h` tidak ikut di-commit (lihat .gitignore).
#pragma once

#define WIFI_SSID     "Nama_WiFi_Anda"
#define WIFI_PASSWORD "Password_WiFi_Anda"

// URL lengkap endpoint presensi RFID.
#define SERVER_URL "http://192.168.1.10/rfid-esp32-attendance/public/api/esp.php"

// Harus sama dengan 'device_key' di config/config.php.
#define DEVICE_KEY "ganti-dengan-kunci-rahasia"
