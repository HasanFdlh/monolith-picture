<?php

return [
    // Example (Windows): powershell -Command "Start-Process -FilePath '{file}' -Verb Print"
    // {file} = ZIP berisi semua foto. {strip} = gambar mock strip hasil akhir
    // (PNG; foto pertama bila mock belum ada) - pakai {strip} untuk mencetak.
    // Example: powershell -Command "Start-Process -FilePath '{strip}' -Verb Print"
    'printer_command' => env('PHOTOBOOTH_PRINTER_COMMAND'),

    // Kalau true, API tidak mau membuat/mengisi/mengubah sesi foto sebelum
    // sesinya berstatus paid (lihat Api\PaymentController).
    'require_payment' => (bool) env('PHOTOBOOTH_REQUIRE_PAYMENT', true),

    // Ukuran standar (px) SEMUA gambar background di dashboard. Gambar yang
    // diupload otomatis dipotong di tengah agar pas dengan ukuran ini.
    'background_width' => (int) env('PHOTOBOOTH_BACKGROUND_WIDTH', 465),
    'background_height' => (int) env('PHOTOBOOTH_BACKGROUND_HEIGHT', 659),

    // Jumlah jepretan per sesi (customer memilih foto terbaik untuk slot frame).
    // Harus sama dengan kBoothShotsPerSession di aplikasi Flutter.
    'shots_per_session' => (int) env('PHOTOBOOTH_SHOTS_PER_SESSION', 8),
];
