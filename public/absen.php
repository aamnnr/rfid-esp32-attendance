<?php
/**
 * Kiosk presensi berbasis browser: tap kartu pada reader USB (atau ketik kode),
 * foto diambil dari webcam lalu dikirim ke api/absen.php.
 */
require __DIR__ . '/../src/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Presensi - <?= e(config('app_name')) ?></title>
    <link href="<?= e(asset('css/bootstrap.css')) ?>" rel="stylesheet">
    <style>
        video {
            max-width: 100%;
            height: auto;
            border-radius: .25rem;
            background: #000;
        }
    </style>
</head>

<body class="bg-primary">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header">
                                    <h3 class="text-center font-weight-light my-4"><?= e(config('app_name')) ?></h3>
                                </div>
                                <div class="card-body">
                                    <div id="response" aria-live="polite"></div>
                                    <form action="<?= e(url('api/absen.php')) ?>" method="POST" id="absenForm">
                                        <div class="text-center">
                                            <video id="video" autoplay playsinline muted></video>
                                        </div>
                                        <h1 class="text-center" id="clock">--:--:--</h1>
                                        <div class="form-group">
                                            <h6 class="text-center text-muted">Silakan TAP kartu Anda</h6>
                                            <input class="form-control py-4 text-center" name="rfid" id="rfid" type="text"
                                                placeholder="TAP kartu Anda" maxlength="32" autocomplete="off" autofocus required>
                                        </div>
                                        <div class="text-center">
                                            <button class="btn btn-primary" type="submit" id="submitButton">Kirim</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center">
                                    <div class="small"><a href="<?= e(url('index.php')) ?>">Ke panel admin</a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        (function () {
            "use strict";

            var video = document.getElementById("video");
            var form = document.getElementById("absenForm");
            var rfidInput = document.getElementById("rfid");
            var button = document.getElementById("submitButton");
            var responseBox = document.getElementById("response");
            var canvas = document.createElement("canvas");

            function showAlert(type, message) {
                var alert = document.createElement("div");
                alert.className = "alert alert-" + type + " text-center h4";
                alert.setAttribute("role", "alert");
                alert.textContent = message;
                responseBox.replaceChildren(alert);
            }

            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(function (stream) { video.srcObject = stream; })
                    .catch(function (err) { showAlert("danger", "Kamera tidak dapat diakses: " + err.name); });
            } else {
                showAlert("danger", "Browser tidak mendukung akses kamera (butuh HTTPS atau localhost).");
            }

            function capturePhoto() {
                if (!video.videoWidth) {
                    return null;
                }
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext("2d").drawImage(video, 0, 0);
                return canvas.toDataURL("image/jpeg", 0.85);
            }

            form.addEventListener("submit", function (event) {
                event.preventDefault();

                var photo = capturePhoto();
                if (!photo) {
                    showAlert("danger", "Kamera belum siap. Coba lagi.");
                    return;
                }

                var data = new FormData(form);
                data.append("image", photo);

                button.disabled = true;
                showAlert("warning", "Memproses...");

                fetch(form.action, { method: "POST", body: data, credentials: "same-origin" })
                    .then(function (res) { return res.json(); })
                    .then(function (res) { showAlert(res.status ? "success" : "danger", res.message); })
                    .catch(function () { showAlert("danger", "Gagal terhubung ke server."); })
                    .finally(function () {
                        button.disabled = false;
                        rfidInput.value = "";
                        rfidInput.focus();
                    });
            });

            function pad(n) { return n < 10 ? "0" + n : String(n); }
            function tick() {
                var now = new Date();
                document.getElementById("clock").textContent =
                    pad(now.getHours()) + ":" + pad(now.getMinutes()) + ":" + pad(now.getSeconds());
            }
            tick();
            setInterval(tick, 1000);
        })();
    </script>
</body>

</html>
