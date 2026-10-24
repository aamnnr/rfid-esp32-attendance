/*
 * Reader RFID presensi (NodeMCU ESP8266 + MFRC522 + LCD I2C 16x2).
 *
 * Alur: kartu di-tap -> GET SERVER_URL?Data=<UID> -> tampilkan respons di LCD.
 * Jika server merespons 200 (presensi tercatat), pin PIN_CAMERA dipulsakan LOW
 * untuk memicu ESP32-CAM mengambil dan mengunggah foto.
 *
 * Library: MFRC522 (miguelbalboa), LiquidCrystal_I2C, board ESP8266.
 * Konfigurasi WiFi/server ada di secrets.h (salin dari secrets.example.h).
 */
#include <SPI.h>
#include <MFRC522.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <LiquidCrystal_I2C.h>

#include "secrets.h"

constexpr uint8_t RST_PIN    = D4;
constexpr uint8_t SS_PIN     = D8;
constexpr uint8_t PIN_BUZZER = D0;  // aktif LOW
constexpr uint8_t PIN_CAMERA = D3;  // ke GPIO13 ESP32-CAM, aktif LOW

constexpr uint16_t HTTP_TIMEOUT_MS      = 8000;
constexpr uint16_t RESULT_DISPLAY_MS    = 2000;
constexpr uint32_t WIFI_CONNECT_TIMEOUT = 20000;

LiquidCrystal_I2C lcd(0x27, 16, 2);
MFRC522 rfid(SS_PIN, RST_PIN);
WiFiClient wifiClient;

void lcdShow(const String &line1, const String &line2) {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print(line1.substring(0, 16));
  lcd.setCursor(0, 1);
  lcd.print(line2.substring(0, 16));
}

void showReady() {
  lcdShow("  Silakan Tap", "   Kartu Anda");
}

void beep(uint16_t ms) {
  digitalWrite(PIN_BUZZER, LOW);
  delay(ms);
  digitalWrite(PIN_BUZZER, HIGH);
}

void triggerCamera() {
  digitalWrite(PIN_CAMERA, LOW);
  delay(500);
  digitalWrite(PIN_CAMERA, HIGH);
}

bool connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) {
    return true;
  }

  lcdShow("Menghubungkan", "WiFi...");
  Serial.print("Menghubungkan ke ");
  Serial.println(WIFI_SSID);

  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  uint32_t start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < WIFI_CONNECT_TIMEOUT) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi gagal terhubung");
    lcdShow("WiFi Gagal", "Coba lagi...");
    return false;
  }

  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
  return true;
}

String readUid() {
  String uid;
  for (byte i = 0; i < rfid.uid.size; i++) {
    if (rfid.uid.uidByte[i] < 0x10) {
      uid += '0';
    }
    uid += String(rfid.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();
  return uid;
}

void setup() {
  Serial.begin(115200);

  pinMode(PIN_CAMERA, OUTPUT);
  digitalWrite(PIN_CAMERA, HIGH);
  pinMode(PIN_BUZZER, OUTPUT);
  digitalWrite(PIN_BUZZER, HIGH);

  lcd.init();
  lcd.backlight();
  lcdShow("  ABSEN MAKMUR", "     SEJATI");

  WiFi.persistent(false);
  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  connectWiFi();

  SPI.begin();
  rfid.PCD_Init();

  beep(300);
  Serial.println("Siap");
  showReady();
}

void loop() {
  if (!connectWiFi()) {
    delay(2000);
    showReady();
    return;
  }

  if (!rfid.PICC_IsNewCardPresent() || !rfid.PICC_ReadCardSerial()) {
    return;
  }

  String uid = readUid();
  Serial.println("Kartu: " + uid);
  beep(200);
  lcdShow("Memproses...", uid);

  HTTPClient http;
  http.setTimeout(HTTP_TIMEOUT_MS);

  int httpCode = -1;
  String payload;
  if (http.begin(wifiClient, String(SERVER_URL) + "?Data=" + uid)) {
    http.addHeader("X-Device-Key", DEVICE_KEY);
    httpCode = http.GET();
    if (httpCode > 0) {
      payload = http.getString();
    }
    http.end();
  }
  Serial.printf("HTTP %d: %s\n", httpCode, payload.c_str());

  if (httpCode <= 0) {
    lcdShow("Server Tidak", "Terjangkau");
    beep(100);
    delay(100);
    beep(100);
  } else {
    // Format respons: "<baris 1>|<baris 2>"
    int separator = payload.indexOf('|');
    String line1 = separator >= 0 ? payload.substring(0, separator) : payload;
    String line2 = separator >= 0 ? payload.substring(separator + 1) : "";
    line1.trim();
    line2.trim();
    lcdShow(line1, line2);

    if (httpCode == HTTP_CODE_OK) {
      triggerCamera();
    } else {
      beep(100);
      delay(100);
      beep(100);
    }
  }

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  delay(RESULT_DISPLAY_MS);
  showReady();
}
