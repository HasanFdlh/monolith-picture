<?php

return [
    // Example (Windows): powershell -Command "Start-Process -FilePath '{file}' -Verb Print"
    // The {file} placeholder is replaced with the generated ZIP file.
    'printer_command' => env('PHOTOBOOTH_PRINTER_COMMAND'),
];
