@extends('layouts.kiosk')

@section('title', $title)

@section('content')
    <div class="text-center mb-4">
        <div class="h4 kiosk-brand mb-1"><i class="fa-solid fa-camera-retro me-2"></i>Luminash Photobooth</div>
        <p class="text-muted-2 mb-0">Pilih paket foto, bayar, lalu mulai sesi.</p>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @unless ($midtransConfigured)
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            MIDTRANS_SERVER_KEY belum diisi, jadi tombol bayar belum bisa jalan.
        </div>
    @endunless

    <form method="POST" action="{{ route('checkout.store') }}" id="checkout-form">
        @csrf

        <div class="row g-3">
            @forelse ($packages as $package)
                <div class="col-md-6">
                    <label class="package-card p-3 d-block h-100">
                        <input type="radio" name="photo_package_id" value="{{ $package->id }}"
                            {{ old('photo_package_id') == $package->id || $loop->first ? 'checked' : '' }} required>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold">{{ $package->name }}</div>
                                <div class="text-muted-2 small">{{ $package->takes }} kali jepret</div>
                            </div>
                            <div class="price-tag">Rp{{ number_format((float) $package->price, 0, ',', '.') }}</div>
                        </div>
                        @if ($package->description)
                            <div class="text-muted-2 small mt-2">{{ $package->description }}</div>
                        @endif
                    </label>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning mb-0">
                        Belum ada paket aktif. Jalankan seeder:
                        <code>php artisan db:seed --class=PhotoPackageSeeder</code>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="panel p-3 mt-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small">Nama (opsional)</label>
                    <input name="customer_name" value="{{ old('customer_name') }}" class="form-control"
                        placeholder="Nama kamu">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Email (opsional)</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control"
                        placeholder="email@contoh.com">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Booth</label>
                    <select name="booth_id" class="form-select">
                        <option value="">Default</option>
                        @foreach ($booths as $booth)
                            <option value="{{ $booth->id }}" {{ old('booth_id') == $booth->id ? 'selected' : '' }}>
                                {{ $booth->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100 mt-3"
            @disabled(!$midtransConfigured || $packages->isEmpty())>
            <i class="fa-solid fa-credit-card me-2"></i>Bayar Sekarang
        </button>
        <p class="text-center text-muted-2 small mt-2 mb-0">Kamu akan diarahkan ke halaman pembayaran Midtrans.</p>
    </form>

    @push('scripts')
        <script>
            (() => {
                const cards = document.querySelectorAll('.package-card');
                const sync = () => cards.forEach(card => {
                    card.classList.toggle('selected', card.querySelector('input').checked);
                });
                cards.forEach(card => card.querySelector('input').addEventListener('change', sync));
                sync();
            })();
        </script>
    @endpush
@endsection
