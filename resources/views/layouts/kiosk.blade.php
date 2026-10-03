<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Photobooth')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: dark; }
        body { background:#0f172a; color:#e2e8f0; font-family:'Inter',sans-serif; min-height:100vh; }
        .kiosk-wrap { max-width:1000px; margin:0 auto; padding:2rem 1rem 4rem; }
        .kiosk-brand { font-weight:700; letter-spacing:.02em; }
        .panel { background:#1e293b; border:1px solid #334155; border-radius:1rem; }
        .text-muted-2 { color:#94a3b8 !important; }
        .form-control, .form-select { background:#0f172a; border-color:#334155; color:#e2e8f0; }
        .form-control:focus, .form-select:focus { background:#0f172a; color:#e2e8f0; border-color:#38bdf8; box-shadow:0 0 0 .2rem rgba(56,189,248,.2); }
        .package-card { cursor:pointer; border:2px solid #334155; border-radius:1rem; background:#1e293b; transition:.15s; position:relative; }
        .package-card:hover { border-color:#475569; }
        .package-card.selected { border-color:#38bdf8; box-shadow:0 0 0 3px rgba(56,189,248,.25); }
        .package-card input { position:absolute; opacity:0; pointer-events:none; }
        .price-tag { font-size:1.35rem; font-weight:700; color:#38bdf8; }
    </style>
    @stack('styles')
</head>

<body>
    <div class="kiosk-wrap">
        @yield('content')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
