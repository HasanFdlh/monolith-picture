@extends('layouts.kiosk')

@section('title', $title)

@section('content')
    <div class="text-center mb-4">
        <div class="h4 kiosk-brand mb-1"><i class="fa-solid fa-money-bill-wave me-2"></i>Menunggu Pembayaran</div>
        <p class="text-muted-2 mb-0">Order <code>{{ $payment->order_id }}</code></p>
    </div>

    <div class="panel p-4">
        <div class="row text-center">
            <div class="col-md-4 mb-3">
                <div class="text-muted-2 small">Paket</div>
                <div class="fw-semibold">{{ $payment->photoPackage->name ?? 'Paket Foto' }}</div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="text-muted-2 small">Jumlah</div>
                <div class="fw-semibold">Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="text-muted-2 small">Batas waktu</div>
                <div class="fw-semibold" id="countdown">--:--</div>
            </div>
        </div>

        <div id="status-box" class="alert alert-info mb-0">
            <span id="status-text">Menunggu konfirmasi pembayaran...</span>
            <div class="progress mt-2" style="height:6px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width:100%"></div>
            </div>
        </div>
    </div>

    <div class="d-grid gap-2 mt-3" id="actions" style="display:none;">
        <a href="{{ route('checkout.index') }}" class="btn btn-outline-secondary">Pilih paket lain</a>
    </div>

    @push('scripts')
        <script>
            (() => {
                const statusUrl = @json(route('checkout.status.json', $payment));
                const fallbackCaptureUrl = @json($payment->session ? route('capture.show', $payment->session) : null);
                const expiresAt = new Date(@json(optional($payment->expires_at)->toIso8601String()));
                const expiryMinutes = {{ $expiryMinutes }};

                const statusBox = document.getElementById('status-box');
                const statusText = document.getElementById('status-text');
                const countdown = document.getElementById('countdown');
                const actions = document.getElementById('actions');

                let finished = false;

                // Kunci tombol back / close selama pembayaran belum selesai.
                window.addEventListener('beforeunload', event => {
                    if (finished) return;
                    event.preventDefault();
                    event.returnValue = '';
                });
                history.pushState(null, '', location.href);
                window.addEventListener('popstate', () => {
                    if (finished) return;
                    history.pushState(null, '', location.href);
                    alert('Selesaikan pembayaran dulu sebelum keluar dari halaman ini.');
                });

                const tick = () => {
                    const diff = Math.max(0, Math.floor((expiresAt.getTime() - Date.now()) / 1000));
                    const m = String(Math.floor(diff / 60)).padStart(2, '0');
                    const s = String(diff % 60).padStart(2, '0');
                    countdown.textContent = `${m}:${s}`;
                    if (diff <= 0 && !finished) {
                        statusText.textContent = `Batas waktu pembayaran (${expiryMinutes} menit) habis. Silakan ulangi.`;
                    }
                };
                tick();
                setInterval(tick, 1000);

                const poll = async () => {
                    if (finished) return;
                    try {
                        const response = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
                        const data = await response.json();

                        if (data.paid) {
                            finished = true;
                            statusBox.className = 'alert alert-success mb-0';
                            statusText.textContent = 'Pembayaran sukses! Mengalihkan ke sesi foto...';
                            window.location.href = data.redirect || fallbackCaptureUrl;
                            return;
                        }
                        if (data.expired) {
                            finished = true;
                            statusBox.className = 'alert alert-danger mb-0';
                            statusText.textContent = 'Pembayaran kadaluarsa / gagal. Silakan pilih paket lagi.';
                            actions.style.display = 'block';
                            return;
                        }
                        if (data.status === 'pending') {
                            statusText.textContent = 'Menunggu konfirmasi pembayaran...';
                        }
                    } catch (error) {
                        // biarkan polling berlanjut
                    }
                    setTimeout(poll, 3000);
                };
                poll();
            })();
        </script>
    @endpush
@endsection
