@extends('layouts.app')

@section('title','Session Detail')

@section('content')

<div class="container mt-4">

    {{-- HEADER --}}
    <div class="mb-4">

        <h3>{{ $session->booth->name ?? 'No Booth' }}</h3>

        <p class="text-muted mb-1">
            Session Code: {{ $session->session_code }}
        </p>

        <p class="text-muted">
            {{ $session->created_at->format('d M Y H:i') }}
        </p>

        <div class="d-flex gap-4">

            <span>📸 {{ $session->media->count() }} Photos</span>

            <span>📦 {{ number_format($session->media->sum('size') / 1024, 2) }} KB</span>

            @if ($session->printed_at)
                <span>🖨️ Dicetak {{ $session->print_count }}x (terakhir {{ $session->printed_at->format('d M Y H:i') }})</span>
            @endif

        </div>

        <div class="mt-3">
            <a href="{{ route('sessions.download', $session->id) }}"
               class="btn btn-success">
                Download ZIP
            </a>
        </div>

    </div>

    {{-- HASIL AKHIR (frame + background + foto pilihan + filter) --}}
    @if (!empty($result))
        <style>
            .pb-result { max-width: 100%; }
            .pb-stage { display: flex; gap: 16px; justify-content: center; margin: 0 auto; width: 100%; }
            .pb-stage.pb-column { flex-direction: column; }
            .pb-sheet { position: relative; flex: 1 1 0; min-width: 0; background: #fff; border-radius: 10px;
                        overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,.18); }
            .pb-sheet.pb-framed { border: 6px solid #d9cdb7; padding: 3%; aspect-ratio: auto; }
            .pb-bg, .pb-overlay { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: fill; }
            .pb-overlay { pointer-events: none; }
            .pb-area { position: absolute; }
            .pb-slot { position: absolute; }
            .pb-grid { display: grid; gap: 2%; width: 100%; height: 100%; }
            .pb-grid.pb-auto { height: auto; }
            .pb-grid.pb-auto .pb-cell { aspect-ratio: 1.15; }
            .pb-cell { position: relative; overflow: hidden; border-radius: 6px; background: #ecebe6; min-height: 0; }
            .pb-slot .pb-cell { width: 100%; height: 100%; border-radius: 0; }
            .pb-cell img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; user-select: none; }
            .pb-vig { position: absolute; inset: 0; pointer-events: none; }
            .pb-empty { background: #ecebe6; }
            .pb-filters { display: flex; gap: 10px; overflow-x: auto; padding: 6px 2px 10px; scroll-snap-type: x proximity; }
            .pb-fitem { flex: 0 0 auto; width: 84px; border: 0; background: transparent; padding: 0; text-align: center;
                        cursor: pointer; scroll-snap-align: start; }
            .pb-fthumb { width: 84px; height: 84px; border-radius: 14px; border: 3px solid transparent; outline: 1px solid rgba(0,0,0,.12); }
            .pb-fitem.active .pb-fthumb { border-color: #6c4ce6; }
            .pb-flabel { display: block; margin-top: 6px; font-size: 12px; font-weight: 500; color: #4b5563; }
            .pb-fitem.active .pb-flabel { color: #6c4ce6; font-weight: 700; }
        </style>

        <div class="card shadow-sm border-0 rounded-4 p-4 mb-4 pb-result" id="result-card">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h5 class="mb-0">Hasil Akhir</h5>
                    <small class="text-muted">
                        {{ $result['composition']['frame_name'] ?? 'Frame' }}
                        @if (($result['composition']['print_mode'] ?? '') === 'duplicate') • Foto Double @endif
                        • Filter: <strong data-pb-filter-name>-</strong>
                    </small>
                </div>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-outline-secondary btn-sm me-2" data-pb-reset disabled>
                        Kembalikan
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" data-pb-save disabled>
                        Simpan filter
                    </button>
                    <span data-pb-status class="small ms-2"></span>
                </div>
            </div>

            <div class="pb-stage" data-pb-stage></div>

            <div class="mt-4">
                <div class="fw-semibold mb-1">Pilih filter</div>
                <div class="pb-filters" data-pb-filters></div>
            </div>
        </div>

        <script src="{{ asset('js/session-composition.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                SessionComposition.init(
                    document.getElementById('result-card'),
                    @json($result),
                    { csrf: '{{ csrf_token() }}' }
                );
            });
        </script>
    @endif

    {{-- GALLERY GRID --}}
    @if (!empty($result))
        <h5 class="mb-3">Semua foto jepretan</h5>
    @endif

    <div class="row g-3">

        @foreach($session->media as $media)

        <div class="col-lg-3 col-md-4 col-sm-6">

            <div class="gallery-item">

                <img src="{{ asset(ltrim($media->path, '/')) }}" class="img-fluid rounded shadow-sm" alt="{{ $media->file_name }}">

            </div>

        </div>

        @endforeach

    </div>

</div>

@endsection
