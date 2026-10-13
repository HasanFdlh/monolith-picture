@extends('layouts.app')

@section('title', 'Background')

@section('content')
  <style>
    .bg-upload{display:flex;gap:14px;align-items:center;flex-wrap:wrap}
    .bg-upload .fb-btn{font-size:.95rem;padding:.65rem 1.1rem}
    .bg-upload small{color:#64748b}
    .bg-name{display:flex;gap:6px}
    .bg-name input{flex:1;min-width:0;border:1px solid #cbd5e1;border-radius:6px;padding:.3rem .5rem;font-size:.85rem}
    .bg-tools{display:flex;gap:6px;flex-wrap:wrap}
    .bg-tools form{margin:0}
    .bg-tools .fb-btn{padding:.35rem .6rem;font-size:.78rem}
    .fb-card.is-off{opacity:.55}
    .bg-std{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:12px;padding:12px 14px;border-radius:12px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:.88rem}
    .bg-std form{margin:0}
    .fb-badge.is-warn{background:#ffedd5;color:#9a3412}
    .bg-empty{margin-top:20px;padding:32px;text-align:center;border:2px dashed #cbd5e1;border-radius:16px;color:#64748b}
  </style>

  <div class="frame-shell">

    @if (session('success'))
      <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger mt-3">{{ $errors->first() }}</div>
    @endif

    <div class="upload-panel">
      <h2>Background</h2>
      <p>
        Upload gambar background yang <strong>sudah jadi</strong> (boleh banyak sekaligus). Di aplikasi frame
        awalnya tampil <em>default</em>; setelah customer menyusun foto, mereka memilih salah satu background di
        sini. Satu gambar berlaku untuk <strong>semua frame</strong>: susunan foto menyesuaikan bentuk area foto
        (gambar portrait &rarr; foto tersusun tegak, landscape &rarr; mendatar). Tekan <em>Sync Server</em> di
        aplikasi bila belum berubah.
      </p>

      <form class="bg-upload" action="{{ route('frame-backgrounds.store') }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        <label class="fb-btn">
          <i class="fa-solid fa-cloud-arrow-up"></i> Upload background
          <input type="file" name="files[]" accept="image/png,image/jpeg,image/webp" multiple hidden
            onchange="this.form.submit()">
        </label>
        <small>
          PNG / JPG / WEBP, maks 10 MB per file, sampai 20 file sekaligus. Semua gambar otomatis dijadikan
          <strong>{{ $standardWidth }}&times;{{ $standardHeight }} px</strong> (dipotong di tengah agar pas, tidak gepeng),
          jadi sebaiknya upload gambar dengan bentuk portrait yang mirip.
          @if (!$gdAvailable)
            <strong style="color:#b91c1c">Ekstensi PHP GD belum aktif, ukuran asli yang dipakai.</strong>
          @endif
        </small>
      </form>
    </div>

    @if ($mismatched > 0)
      <div class="bg-std">
        <span>
          <strong>{{ $mismatched }} background</strong> ukurannya belum {{ $standardWidth }}&times;{{ $standardHeight }} px.
        </span>
        <form action="{{ route('frame-backgrounds.normalize') }}" method="POST"
          onsubmit="return confirm('Samakan {{ $mismatched }} background ke {{ $standardWidth }}×{{ $standardHeight }} px? Gambar di server ditimpa (dipotong di tengah) dan Area foto yang rasionya berubah dikembalikan ke bawaan.')">
          @csrf
          <button type="submit" class="fb-btn" @disabled(!$gdAvailable)>
            <i class="fa-solid fa-wand-magic-sparkles"></i> Samakan semua ke {{ $standardWidth }}&times;{{ $standardHeight }} px
          </button>
        </form>
      </div>
    @endif

    @if ($backgrounds->isEmpty())
      <div class="bg-empty">
        Belum ada background. Upload gambar di atas; selama kosong, aplikasi memakai tampilan default.
      </div>
    @else
      <div class="fb-grid-list">
        @foreach ($backgrounds as $bg)
          @php
            $aspect = $bg->aspect() ? round($bg->aspect(), 4) : 0.7;
            $area = $bg->area;
          @endphp

          <div class="fb-card {{ $bg->is_active ? '' : 'is-off' }}">
            {{-- Pratinjau: kotak oranye = area tempat foto ditempatkan (atur lewat "Area foto"). --}}
            <div class="fb-sheet"
              style="--aspect: {{ $aspect }};
                @if ($bg->image_url) background-image: url('{{ $bg->image_url }}'); @endif">
              <div class="fb-slots"
                style="left: {{ $area['x'] }}%; top: {{ $area['y'] }}%; width: {{ $area['w'] }}%; height: {{ $area['h'] }}%;
                  grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(4, 1fr);">
                @for ($i = 1; $i <= 8; $i++)
                  <span>{{ $i }}</span>
                @endfor
              </div>
            </div>

            <div class="fb-info">
              <form class="bg-name" method="POST" action="{{ route('frame-backgrounds.rename', $bg) }}">
                @csrf
                @method('PATCH')
                <input type="text" name="name" value="{{ $bg->name }}" maxlength="60" required>
                <button type="submit" class="fb-btn fb-btn-light" title="Simpan nama">
                  <i class="fa-solid fa-check"></i>
                </button>
              </form>
              <small>{{ $bg->width ?? '?' }}&times;{{ $bg->height ?? '?' }} px</small>
              <span class="fb-badge {{ $bg->is_active ? 'is-set' : '' }}">
                {{ $bg->is_active ? 'Aktif di aplikasi' : 'Nonaktif' }}
              </span>
              @if ((int) $bg->width !== $standardWidth || (int) $bg->height !== $standardHeight)
                <span class="fb-badge is-warn">Ukuran beda</span>
              @endif
            </div>

            <div class="bg-tools">
              <a href="{{ route('frame-backgrounds.edit', $bg) }}" class="fb-btn fb-btn-light">
                <i class="fa-solid fa-crop-simple"></i> Area foto
              </a>

              <form action="{{ route('frame-backgrounds.replace', $bg) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <label class="fb-btn fb-btn-light">
                  <i class="fa-solid fa-image"></i> Ganti
                  <input type="file" name="file" accept="image/png,image/jpeg,image/webp" hidden
                    onchange="this.form.submit()">
                </label>
              </form>

              <form action="{{ route('frame-backgrounds.toggle', $bg) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="fb-btn fb-btn-light">
                  <i class="fa-solid {{ $bg->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                  {{ $bg->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
              </form>

              <form action="{{ route('frame-backgrounds.destroy', $bg) }}" method="POST"
                onsubmit="return confirm('Hapus background {{ addslashes($bg->name) }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="fb-btn fb-btn-danger">
                  <i class="fa-regular fa-trash-can"></i>
                </button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
@endsection
