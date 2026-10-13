@extends('layouts.app')

@section('title', 'Hasil Print')

@section('content')

    {{-- RINGKASAN --}}
    <div class="row g-3 mt-1">
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 rounded-4 p-3">
                <small class="text-muted">Total sesi dicetak</small>
                <div class="fs-3 fw-bold">{{ $totalPrinted }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 rounded-4 p-3">
                <small class="text-muted">Dicetak hari ini</small>
                <div class="fs-3 fw-bold">{{ $printedToday }}</div>
            </div>
        </div>
    </div>

    {{-- FILTER --}}
    <form method="GET" class="card shadow-sm border-0 rounded-4 p-3 mt-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                    placeholder="Kode sesi / nama / booth">
            </div>

            <div class="col-md-2">
                <label class="form-label">Booth</label>
                <select name="booth_id" class="form-select">
                    <option value="">Semua booth</option>
                    @foreach ($booths as $booth)
                        <option value="{{ $booth->id }}" {{ request('booth_id') == $booth->id ? 'selected' : '' }}>
                            {{ $booth->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Dari</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control">
            </div>

            <div class="col-md-2">
                <label class="form-label">Sampai</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control">
            </div>

            <div class="col-md-1">
                <label class="form-label">Tampil</label>
                <select name="limit" class="form-select">
                    @foreach ([12, 24, 48, 96] as $n)
                        <option value="{{ $n }}" {{ (int) request('limit', 12) === $n ? 'selected' : '' }}>
                            {{ $n }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('printed.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </div>
    </form>

    @if (session('success'))
        <div class="alert alert-success mt-3 mb-0">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger mt-3 mb-0">{{ $errors->first() }}</div>
    @endif

    {{-- AKSI MASSAL --}}
    @if ($sessions->count() > 0)
        <form id="bulk-form" method="POST" action="{{ route('printed.bulk') }}"
            class="card shadow-sm border-0 rounded-4 p-3 mt-3 d-flex flex-row flex-wrap align-items-center gap-2">
            @csrf
            <div class="form-check me-2 mb-0">
                <input type="checkbox" class="form-check-input" id="select-all">
                <label for="select-all" class="form-check-label">Pilih semua di halaman ini</label>
            </div>
            <span class="text-muted small" id="selected-count">0 dipilih</span>

            <div class="ms-auto d-flex gap-2">
                <button type="submit" name="action" value="unmark" class="btn btn-outline-secondary btn-sm" disabled data-bulk>
                    <i class="fa-solid fa-eye-slash me-1"></i>Keluarkan dari daftar
                </button>
                <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" disabled data-bulk>
                    <i class="fa-solid fa-trash me-1"></i>Hapus permanen
                </button>
            </div>
        </form>
    @endif

    {{-- DAFTAR SESI --}}
    <div class="row mt-4">
        @forelse ($sessions as $session)
            @php
                $photos = $session->media->where('type', 'photo')->values();
                $strip = $session->media->where('type', 'strip')->sortByDesc('id')->first();
                $cover = $strip ?? $photos->first() ?? $session->media->first();
            @endphp

            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                <div class="card shadow-sm border-0 rounded-4 session-card h-100">

                    <div class="position-relative">
                        <img src="{{ $cover ? asset(ltrim($cover->path, '/')) : 'https://placehold.co/600x400' }}"
                            class="card-img-top session-img" alt="{{ $session->session_code }}">

                        <span class="badge bg-dark position-absolute top-0 start-0 m-2">
                            <i class="fa-solid fa-print me-1"></i>{{ $session->print_count }}x dicetak
                        </span>

                        <input type="checkbox" form="bulk-form" name="ids[]" value="{{ $session->id }}"
                            class="form-check-input position-absolute top-0 end-0 m-2 session-check"
                            style="width:1.4rem;height:1.4rem;cursor:pointer" aria-label="Pilih sesi {{ $session->session_code }}">
                    </div>

                    {{-- Strip foto kecil --}}
                    @if ($photos->count() > 1)
                        <div class="d-flex gap-1 px-3 pt-3" style="overflow-x:auto">
                            @foreach ($photos->take(8) as $photo)
                                <a href="{{ asset(ltrim($photo->path, '/')) }}" target="_blank" class="flex-shrink-0">
                                    <img src="{{ asset(ltrim($photo->path, '/')) }}" alt=""
                                        style="width:44px;height:44px;object-fit:cover;border-radius:8px">
                                </a>
                            @endforeach
                            @if ($photos->count() > 8)
                                <span class="small text-muted align-self-center ms-1">+{{ $photos->count() - 8 }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="card-body d-flex flex-column">
                        <h5 class="mb-1">{{ $session->booth->name ?? 'No Booth' }}</h5>

                        <small class="text-muted d-block">
                            <i class="fa-solid fa-print me-1"></i>
                            {{ optional($session->printed_at)->format('d M Y H:i') }}
                        </small>
                        <small class="text-muted d-block">{{ $session->session_code ?? '-' }}</small>
                        @if ($session->photoFrame)
                            <small class="text-muted d-block">Frame: {{ $session->photoFrame->name }}</small>
                        @endif
                        @if ($session->filter)
                            <small class="text-muted d-block">Filter: {{ $session->filter }}</small>
                        @endif

                        <hr>

                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <small class="text-muted">Foto</small>
                                <div><strong>{{ $photos->count() }}</strong></div>
                            </div>
                            <div>
                                <small class="text-muted">Ukuran</small>
                                <div><strong>{{ number_format($session->media->sum('size') / 1024, 2) }} KB</strong></div>
                            </div>
                        </div>

                        <div class="mt-auto d-flex gap-2">
                            <a href="{{ route('sessions.show', $session->id) }}" class="btn btn-primary btn-sm flex-fill">
                                Detail
                            </a>
                            <a href="{{ route('sessions.download', $session->id) }}" class="btn btn-success btn-sm flex-fill">
                                ZIP
                            </a>

                            <div class="dropdown">
                                <button class="btn btn-outline-danger btn-sm dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <form method="POST" action="{{ route('printed.unmark', $session->id) }}"
                                            data-confirm="Keluarkan sesi {{ $session->session_code }} dari Hasil Print? Sesi dan fotonya tetap tersimpan.">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="fa-solid fa-eye-slash me-2"></i>Keluarkan dari daftar
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('printed.destroy', $session->id) }}"
                                            data-confirm="Hapus PERMANEN sesi {{ $session->session_code }} beserta semua fotonya? Tindakan ini tidak bisa dibatalkan.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fa-solid fa-trash me-2"></i>Hapus permanen
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted py-5">
                <i class="fa-solid fa-print fs-1 mb-3 d-block"></i>
                Belum ada sesi yang dicetak.
                <div class="small">Sesi akan muncul di sini setelah pelanggan menekan tombol Cetak di kiosk.</div>
            </div>
        @endforelse
    </div>

    <div class="d-flex justify-content-center">
        {{ $sessions->links() }}
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Konfirmasi untuk form hapus per kartu.
            document.querySelectorAll('form[data-confirm]').forEach(function (f) {
                f.addEventListener('submit', function (e) {
                    if (!confirm(f.dataset.confirm)) e.preventDefault();
                });
            });

            var bulk = document.getElementById('bulk-form');
            if (!bulk) return;
            var checks = Array.prototype.slice.call(document.querySelectorAll('.session-check'));
            var all = document.getElementById('select-all');
            var count = document.getElementById('selected-count');
            var buttons = bulk.querySelectorAll('[data-bulk]');

            function refresh() {
                var n = checks.filter(function (c) { return c.checked; }).length;
                count.textContent = n + ' dipilih';
                buttons.forEach(function (b) { b.disabled = n === 0; });
                all.checked = n > 0 && n === checks.length;
                all.indeterminate = n > 0 && n < checks.length;
            }

            checks.forEach(function (c) { c.addEventListener('change', refresh); });
            all.addEventListener('change', function () {
                checks.forEach(function (c) { c.checked = all.checked; });
                refresh();
            });

            bulk.addEventListener('submit', function (e) {
                var n = checks.filter(function (c) { return c.checked; }).length;
                var action = e.submitter ? e.submitter.value : '';
                var msg = action === 'delete'
                    ? 'Hapus PERMANEN ' + n + ' sesi beserta semua fotonya? Tindakan ini tidak bisa dibatalkan.'
                    : 'Keluarkan ' + n + ' sesi dari Hasil Print? Sesi dan fotonya tetap tersimpan.';
                if (!confirm(msg)) e.preventDefault();
            });

            refresh();
        });
    </script>

@endsection
