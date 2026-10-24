# Sistem Presensi RFID + Foto ESP32-CAM

[![CI](https://github.com/aamnnr/rfid-esp32-attendance/actions/workflows/ci.yml/badge.svg)](https://github.com/aamnnr/rfid-esp32-attendance/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

Sistem presensi siswa berbasis kartu RFID. Setiap tap kartu dicatat sesuai jadwal kelas,
lalu ESP32-CAM mengambil foto sebagai bukti kehadiran. Admin mengelola data melalui panel web PHP.

## Fitur

- **Presensi RFID** lewat reader ESP8266 + MFRC522 dengan tampilan LCD 16x2.
- **Foto bukti** otomatis dari ESP32-CAM, dipasangkan ke presensi yang baru tercatat.
- **Kiosk web** (`absen.php`) sebagai alternatif: reader RFID USB + webcam di browser.
- **Validasi jadwal**: presensi masuk dibuka 60 menit sebelum jam masuk; lewat jam masuk = terlambat;
  presensi pulang mulai jam pulang. Jam masuk = jam pulang berarti libur.
- **Manajemen data** siswa, kelas, jadwal mingguan, dan rekap (ubah status, lihat foto).
- **Pendaftaran kartu mudah**: tap kartu baru di reader, lalu klik *Ambil dari reader* di form siswa.
- **Laporan bulanan** per kelas, bisa diekspor ke Excel atau dicetak.

## Arsitektur

```
 ┌──────────────┐  GET /api/esp.php?Data=UID   ┌──────────────────────┐
 │ ESP8266      │ ───────────────────────────► │                      │
 │ + MFRC522    │ ◄─── "Baris 1|Baris 2" ───── │   Web server PHP     │
 │ + LCD I2C    │                              │   + MySQL            │
 └──────┬───────┘                              │                      │
        │ D3 → GPIO13 (pulsa LOW               │  public/  panel admin│
        │ jika presensi tercatat)              │  public/api/  endpoint│
 ┌──────▼───────┐  POST /api/upload.php (foto) │                      │
 │ ESP32-CAM    │ ───────────────────────────► │                      │
 └──────────────┘                              └──────────────────────┘
```

## Struktur Proyek

```
.
├── config/
│   └── config.example.php   # salin menjadi config.php (tidak di-commit)
├── database/
│   ├── schema.sql           # struktur tabel
│   ├── seed.sql             # data awal (admin, kelas contoh, jadwal)
│   └── migrations/          # upgrade dari database versi lama
├── firmware/
│   ├── esp8266_rfid/        # sketch reader RFID + secrets.example.h
│   └── esp32cam/            # sketch kamera + secrets.example.h
├── public/                  # ← document root web server
│   ├── index.php            # dashboard
│   ├── siswa.php, kelas.php, jadwal.php, rekap.php, laporan.php, ...
│   ├── absen.php            # kiosk presensi web
│   ├── actions/             # handler form admin (POST + CSRF)
│   ├── api/                 # endpoint perangkat & AJAX
│   ├── assets/              # CSS/JS/gambar
│   └── uploads/             # foto siswa & presensi (tidak di-commit)
└── src/                     # kode inti (bootstrap, database, auth, aturan presensi, view)
```

## Persyaratan

- PHP 7.4 atau lebih baru dengan ekstensi `mysqli` (mysqlnd) dan `mbstring`
- MySQL 5.7+ atau MariaDB 10.2+
- Apache (mis. XAMPP) atau Nginx
- Arduino IDE dengan board **ESP8266** dan **ESP32**, serta library **MFRC522** (miguelbalboa)
  dan **LiquidCrystal I2C**

## Instalasi

### 1. Clone repository

```bash
git clone https://github.com/aamnnr/rfid-esp32-attendance.git
```

Dengan XAMPP, letakkan folder di `htdocs/`. File `.htaccess` di root meneruskan semua request ke
`public/`, sehingga aplikasi bisa dibuka di `http://localhost/rfid-esp32-attendance/`.
Di server produksi, sebaiknya arahkan document root langsung ke folder `public/`.

### 2. Buat database

```bash
mysql -u root -p -e "CREATE DATABASE presensi_rfid CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p presensi_rfid < database/schema.sql
mysql -u root -p presensi_rfid < database/seed.sql
```

### 3. Konfigurasi aplikasi

```bash
cp config/config.example.php config/config.php
```

Isi kredensial database di `config/config.php`, lalu isi `device_key` dengan string acak yang
panjang. Kunci yang sama dipakai di firmware.

### 4. Login

Buka `http://localhost/rfid-esp32-attendance/` dan login dengan username **`admin`** dan password
**`admin`**. **Segera ganti password ini di server produksi.**

Untuk menjalankan tanpa Apache selama pengembangan:

```bash
php -S localhost:8000 -t public
```

## Perangkat Keras

### Rangkaian

| ESP8266 (NodeMCU) | Komponen               |
|-------------------|------------------------|
| D8                | MFRC522 SDA (SS)       |
| D4                | MFRC522 RST            |
| D5 / D6 / D7      | MFRC522 SCK / MISO / MOSI |
| D1 / D2           | LCD I2C SCL / SDA      |
| D0                | Buzzer (aktif LOW)     |
| D3                | ESP32-CAM GPIO13 (pemicu foto) |
| GND               | GND ESP32-CAM (wajib satu ground) |

### Upload firmware

Untuk setiap folder di `firmware/`:

1. Salin `secrets.example.h` menjadi `secrets.h` di folder yang sama.
2. Isi SSID/password WiFi, alamat server, dan `DEVICE_KEY` (harus sama dengan `config.php`).
3. Buka file `.ino` di Arduino IDE, pilih board (**NodeMCU 1.0** / **AI Thinker ESP32-CAM**), lalu upload.

`secrets.h` sudah masuk `.gitignore`, jadi kredensial WiFi tidak ikut ter-push.

## API Perangkat

| Endpoint | Metode | Keterangan |
|---|---|---|
| `api/esp.php?Data=<UID>` | GET | Tap kartu. Respons teks `baris1\|baris2` untuk LCD. `200` = tercatat, `404` = kartu belum terdaftar, `409` = ditolak (di luar jam, sudah absen, dll.) |
| `api/upload.php` | POST multipart (`imageFile`) | Foto ESP32-CAM, dipasangkan ke presensi terbaru (maks. 2 menit) yang masih menunggu foto |
| `api/absen.php` | POST (`rfid`, `image` data URL) | Presensi dari kiosk web, respons JSON |

Kedua endpoint perangkat mewajibkan header `X-Device-Key` jika `device_key` diisi di konfigurasi.

## Upgrade dari Versi Lama

Versi sebelumnya memakai file PHP di root repository dan skema `sql/labandroid_esprfid.sql`.
Untuk memakai database lama:

1. Backup database.
2. Jalankan `database/migrations/001_upgrade_legacy.sql` (baca komentar di dalamnya).
3. Pindahkan foto: `rekapfoto/*` → `public/uploads/rekap/`, `image/*` → `public/uploads/siswa/`.
4. Upload ulang firmware. URL endpoint berubah menjadi `public/api/esp.php` dan `public/api/upload.php`.

Password admin lama yang tersimpan sebagai teks biasa otomatis di-hash saat login pertama.

## Troubleshooting

- **Halaman menampilkan "Konfigurasi belum dibuat"**: `config/config.php` belum dibuat (lihat langkah 3).
- **Kamera di kiosk tidak muncul**: browser hanya mengizinkan kamera di `https://` atau `localhost`.
- **LCD menampilkan "Akses Ditolak"**: `DEVICE_KEY` di firmware berbeda dengan `device_key` di `config.php`.
- **Foto tidak muncul di rekap**: pastikan folder `public/uploads/` bisa ditulis web server, dan
  jam server, ESP8266, serta ESP32-CAM tersambung ke jaringan yang sama.
- **Melihat detail error**: set `'debug' => true` di `config/config.php` (jangan dipakai di produksi).
  Error selalu dicatat di log error PHP.

## Kontribusi

1. Fork repository ini.
2. Buat branch: `git checkout -b feature/nama-fitur`.
3. Commit perubahan dan push branch.
4. Buat Pull Request. CI akan memeriksa sintaks PHP secara otomatis.

## Lisensi

Didistribusikan di bawah [MIT License](LICENSE).

## Acknowledgments

- [esp32-camera](https://github.com/espressif/esp32-camera)
- [MFRC522](https://github.com/miguelbalboa/rfid)
- [SB Admin](https://startbootstrap.com/template/sb-admin), [Bootstrap 4](https://getbootstrap.com/),
  [DataTables](https://datatables.net/), [SheetJS](https://sheetjs.com/)
