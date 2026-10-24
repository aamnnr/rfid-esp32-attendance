/**
 * Perilaku umum halaman admin.
 */
(function ($) {
    "use strict";

    // Tabel data: aktifkan DataTables dengan bahasa Indonesia.
    $("table.js-datatable").DataTable({
        order: [],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
            infoEmpty: "Tidak ada data",
            infoFiltered: "(disaring dari _MAX_ data)",
            zeroRecords: "Data tidak ditemukan",
            emptyTable: "Belum ada data",
            paginate: { first: "Awal", last: "Akhir", next: "Berikutnya", previous: "Sebelumnya" }
        }
    });

    // Form dengan atribut data-confirm meminta konfirmasi sebelum dikirim.
    $(document).on("submit", "form[data-confirm]", function (event) {
        if (!window.confirm($(this).data("confirm"))) {
            event.preventDefault();
        }
    });

    // Tombol "Ambil dari reader": isi input RFID dengan kartu terakhir
    // yang di-tap di reader namun belum terdaftar.
    $(document).on("click", "[data-rfid-fetch]", function () {
        var $button = $(this);
        var $input = $($button.data("target"));
        var $help = $($button.data("help"));

        $button.prop("disabled", true);
        $.getJSON($button.data("rfid-fetch"))
            .done(function (response) {
                if (response.status && response.rfid) {
                    $input.val(response.rfid);
                    $help.removeClass("text-danger").text("Kartu di-tap pada " + response.scanned_at + ".");
                } else {
                    $help.addClass("text-danger").text(response.message || "Belum ada kartu baru.");
                }
            })
            .fail(function () {
                $help.addClass("text-danger").text("Gagal mengambil data kartu dari server.");
            })
            .always(function () {
                $button.prop("disabled", false);
            });
    });
})(jQuery);
