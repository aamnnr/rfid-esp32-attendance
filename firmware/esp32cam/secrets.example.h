// Salin file ini menjadi `secrets.h` (satu folder dengan .ino) lalu isi nilainya.
// `secrets.h` tidak ikut di-commit (lihat .gitignore).
#pragma once

#define WIFI_SSID     "Nama_WiFi_Anda"
#define WIFI_PASSWORD "Password_WiFi_Anda"

// Host & path endpoint upload foto (tanpa http://).
#define SERVER_HOST "192.168.1.10"
#define SERVER_PORT 80
#define UPLOAD_PATH "/rfid-esp32-attendance/public/api/upload.php"

// Harus sama dengan 'device_key' di config/config.php.
#define DEVICE_KEY "ganti-dengan-kunci-rahasia"
