<html>
<body>
<p>Halo,</p>

<p>Terima kasih sudah menggunakan photobooth kami. Berikut hasil sesi <strong>{{ $session->session_code }}</strong>.</p>

@if (!empty($stripPath))
    <p><img src="{{ $message->embed($stripPath) }}" alt="Hasil strip" style="max-width:100%;height:auto;border-radius:8px"></p>
@endif

<p>Semua foto (beserta mock hasil strip) ada di file ZIP terlampir.</p>

@php($files = collect($session->media ?? []))
@if ($files->count() > 0)
    <p>Isi file:</p>
    <ul>
    @foreach ($files as $m)
        <li>{{ $m->type === 'strip' ? 'Mock hasil strip' : 'Foto' }} &ndash; {{ $m->file_name }}</li>
    @endforeach
    </ul>
@endif

<p>Salam,<br/>Photobooth</p>
</body>
</html>
