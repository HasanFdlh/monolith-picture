<?php

return [
    // Example (Windows): powershell -Command "Start-Process -FilePath '{file}' -Verb Print"
    // The {file} placeholder is replaced with the generated ZIP file.
    'printer_command' => env('PHOTOBOOTH_PRINTER_COMMAND'),

    // Kalau true, API tidak mau membuat/mengisi/mengubah sesi foto sebelum
    // sesinya berstatus paid (lihat Api\PaymentController).
    'require_payment' => (bool) env('PHOTOBOOTH_REQUIRE_PAYMENT', true),

    // Jumlah jepretan per sesi (customer memilih foto terbaik untuk slot frame).
    // Harus sama dengan kBoothShotsPerSession di aplikasi Flutter.
    'shots_per_session' => (int) env('PHOTOBOOTH_SHOTS_PER_SESSION', 8),
];
