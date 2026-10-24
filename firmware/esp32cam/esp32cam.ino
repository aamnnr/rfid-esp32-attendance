/*
 * Kamera presensi (ESP32-CAM AI-Thinker).
 *
 * Saat PIN_TRIGGER ditarik LOW oleh reader ESP8266, kamera mengambil satu foto
 * lalu mengunggahnya (multipart/form-data, field `imageFile`) ke UPLOAD_PATH.
 * Server memasangkan foto itu ke presensi yang baru saja tercatat.
 *
 * Board: "AI Thinker ESP32-CAM" (paket esp32 by Espressif).
 * Konfigurasi WiFi/server ada di secrets.h (salin dari secrets.example.h).
 */
#include <Arduino.h>
#include <WiFi.h>
#include "soc/soc.h"
#include "soc/rtc_cntl_reg.h"
#include "esp_camera.h"

#include "secrets.h"

// Pin kamera CAMERA_MODEL_AI_THINKER
#define PWDN_GPIO_NUM     32
#define RESET_GPIO_NUM    -1
#define XCLK_GPIO_NUM      0
#define SIOD_GPIO_NUM     26
#define SIOC_GPIO_NUM     27
#define Y9_GPIO_NUM       35
#define Y8_GPIO_NUM       34
#define Y7_GPIO_NUM       39
#define Y6_GPIO_NUM       36
#define Y5_GPIO_NUM       21
#define Y4_GPIO_NUM       19
#define Y3_GPIO_NUM       18
#define Y2_GPIO_NUM        5
#define VSYNC_GPIO_NUM    25
#define HREF_GPIO_NUM     23
#define PCLK_GPIO_NUM     22

constexpr int PIN_STATUS_LED = 33;  // LED merah onboard, aktif LOW
constexpr int PIN_FLASH_LED  = 4;   // LED flash
constexpr int PIN_TRIGGER    = 13;  // dari D3 ESP8266, aktif LOW

constexpr uint32_t WIFI_CONNECT_TIMEOUT = 20000;
constexpr uint32_t RESPONSE_TIMEOUT_MS  = 10000;
constexpr size_t   CHUNK_SIZE           = 1024;

static const char *BOUNDARY = "PresensiBoundary7MA4YWxk";

bool lastTriggered = false;

bool connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) {
    return true;
  }

  Serial.printf("Menghubungkan ke %s", WIFI_SSID);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  uint32_t start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < WIFI_CONNECT_TIMEOUT) {
    Serial.print(".");
    delay(500);
  }
  Serial.println();

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi gagal terhubung");
    return false;
  }
  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
  return true;
}

void initCamera() {
  camera_config_t config;
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer = LEDC_TIMER_0;
  config.pin_d0 = Y2_GPIO_NUM;
  config.pin_d1 = Y3_GPIO_NUM;
  config.pin_d2 = Y4_GPIO_NUM;
  config.pin_d3 = Y5_GPIO_NUM;
  config.pin_d4 = Y6_GPIO_NUM;
  config.pin_d5 = Y7_GPIO_NUM;
  config.pin_d6 = Y8_GPIO_NUM;
  config.pin_d7 = Y9_GPIO_NUM;
  config.pin_xclk = XCLK_GPIO_NUM;
  config.pin_pclk = PCLK_GPIO_NUM;
  config.pin_vsync = VSYNC_GPIO_NUM;
  config.pin_href = HREF_GPIO_NUM;
  config.pin_sscb_sda = SIOD_GPIO_NUM;
  config.pin_sscb_scl = SIOC_GPIO_NUM;
  config.pin_pwdn = PWDN_GPIO_NUM;
  config.pin_reset = RESET_GPIO_NUM;
  config.xclk_freq_hz = 20000000;
  config.pixel_format = PIXFORMAT_JPEG;

  if (psramFound()) {
    config.frame_size = FRAMESIZE_SVGA;
    config.jpeg_quality = 10;  // 0-63, makin kecil makin bagus
    config.fb_count = 2;
  } else {
    config.frame_size = FRAMESIZE_CIF;
    config.jpeg_quality = 12;
    config.fb_count = 1;
  }

  esp_err_t err = esp_camera_init(&config);
  if (err != ESP_OK) {
    Serial.printf("Inisialisasi kamera gagal: 0x%x\n", err);
    delay(1000);
    ESP.restart();
  }
}

String sendPhoto() {
  camera_fb_t *fb = esp_camera_fb_get();
  if (!fb) {
    Serial.println("Gagal mengambil foto");
    return "";
  }

  if (!connectWiFi()) {
    esp_camera_fb_return(fb);
    return "";
  }

  WiFiClient client;
  Serial.printf("Mengunggah %u byte ke %s:%d%s\n", fb->len, SERVER_HOST, SERVER_PORT, UPLOAD_PATH);
  if (!client.connect(SERVER_HOST, SERVER_PORT)) {
    Serial.println("Koneksi ke server gagal");
    esp_camera_fb_return(fb);
    return "";
  }

  String head = String("--") + BOUNDARY + "\r\n"
                "Content-Disposition: form-data; name=\"imageFile\"; filename=\"capture.jpg\"\r\n"
                "Content-Type: image/jpeg\r\n\r\n";
  String tail = String("\r\n--") + BOUNDARY + "--\r\n";
  size_t totalLen = head.length() + fb->len + tail.length();

  client.printf("POST %s HTTP/1.1\r\n", UPLOAD_PATH);
  client.printf("Host: %s\r\n", SERVER_HOST);
  client.printf("X-Device-Key: %s\r\n", DEVICE_KEY);
  client.printf("Content-Length: %u\r\n", totalLen);
  client.printf("Content-Type: multipart/form-data; boundary=%s\r\n", BOUNDARY);
  client.print("Connection: close\r\n\r\n");
  client.print(head);

  for (size_t sent = 0; sent < fb->len; sent += CHUNK_SIZE) {
    size_t len = min(CHUNK_SIZE, fb->len - sent);
    client.write(fb->buf + sent, len);
  }
  client.print(tail);
  esp_camera_fb_return(fb);

  // Baca status line + body respons.
  String response;
  uint32_t start = millis();
  while ((client.connected() || client.available()) && millis() - start < RESPONSE_TIMEOUT_MS) {
    while (client.available()) {
      response += (char)client.read();
      start = millis();
    }
    delay(10);
  }
  client.stop();

  int bodyStart = response.indexOf("\r\n\r\n");
  String statusLine = response.substring(0, response.indexOf("\r\n"));
  String body = bodyStart >= 0 ? response.substring(bodyStart + 4) : "";
  Serial.println(statusLine);
  Serial.println(body);
  return statusLine;
}

void setup() {
  WRITE_PERI_REG(RTC_CNTL_BROWN_OUT_REG, 0);  // cegah reset brownout saat kamera aktif
  Serial.begin(115200);

  pinMode(PIN_STATUS_LED, OUTPUT);
  digitalWrite(PIN_STATUS_LED, HIGH);
  pinMode(PIN_FLASH_LED, OUTPUT);
  digitalWrite(PIN_FLASH_LED, LOW);
  pinMode(PIN_TRIGGER, INPUT_PULLUP);

  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  connectWiFi();
  initCamera();

  digitalWrite(PIN_STATUS_LED, LOW);
  Serial.println("Siap");
}

void loop() {
  // Ambil foto sekali setiap transisi HIGH -> LOW.
  bool triggered = digitalRead(PIN_TRIGGER) == LOW;
  if (triggered && !lastTriggered) {
    Serial.println("Trigger diterima");
    digitalWrite(PIN_FLASH_LED, HIGH);
    sendPhoto();
    digitalWrite(PIN_FLASH_LED, LOW);
  }
  lastTriggered = triggered;
  delay(20);
}
