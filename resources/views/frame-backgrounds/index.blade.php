@extends('layouts.app')

@section('title', 'Frame Backgrounds')

@section('content')
  <div class="frame-shell">

    @if (session('success'))
      <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger mt-3">{{ $errors->first() }}</div>
    @endif

    <div class="upload-panel">
      <h2>Background Frame Bawaan</h2>
      <p>
        Gambar yang di-upload mengisi area <strong>putih</strong> di sekeliling dan di sela slot foto pada
        frame bawaan aplikasi. Aplikasi mengambilnya otomatis dari <code>/api/frame-backgrounds</code>
        (tekan <em>Sync Server</em> di aplikasi bila belum berubah).
      </p>
    </div>

    <div class="fb-grid-list">
      @foreach ($frames as $frame)
        @php
          $bg = $frame['background'];
          $recW = 1200;
          $recH = (int) round($recW / $frame['aspect']);
          $aspect = ($bg && $bg->aspect()) ? round($bg->aspect(), 4) : $frame['aspect'];
          $area = $bg ? $bg->area : \App\Models\FrameBackground::DEFAULT_AREA;
        @endphp

        <div class="fb-card">
          <div class="fb-sheet"
            style="--aspect: {{ $aspect }};
              @if ($bg && $bg->image_url) background-image: url('{{ $bg->image_url }}'); @endif">
            <div class="fb-slots"
              style="left: {{ $area['x'] }}%; top: {{ $area['y'] }}%; width: {{ $area['w'] }}%; height: {{ $area['h'] }}%;
                grid-template-columns: repeat({{ $frame['cols'] }}, 1fr);
                grid-template-rows: repeat({{ $frame['rows'] }}, 1fr);">
              @for ($i = 1; $i <= $frame['cols'] * $frame['rows']; $i++)
                <span>{{ $i }}</span>
              @endfor
            </div>
          </div>

          <div class="fb-info">
            <h4>{{ $frame['name'] }}</h4>
            <small>{{ $frame['slots'] }} slot &middot; disarankan {{ $recW }}&times;{{ $recH }} px</small>
            <span class="fb-badge {{ $bg ? 'is-set' : '' }}">
              {{ $bg ? 'Background aktif' : 'Putih (default)' }}
            </span>
          </div>

          <div class="fb-actions">
            <form action="{{ route('frame-backgrounds.update', $frame['key']) }}" method="POST"
              enctype="multipart/form-data">
              @csrf
              <label class="fb-btn">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                {{ $bg ? 'Ganti gambar' : 'Upload gambar' }}
                <input type="file" name="file" accept="image/png,image/jpeg,image/webp" hidden
                  onchange="this.form.submit()">
              </label>
            </form>

            @if ($bg)
              <a href="{{ route('frame-backgrounds.edit', $frame['key']) }}" class="fb-btn fb-btn-light">
                <i class="fa-solid fa-crop-simple"></i> Atur area foto
              </a>

              <form action="{{ route('frame-backgrounds.destroy', $frame['key']) }}" method="POST"
                onsubmit="return confirm('Hapus background {{ $frame['name'] }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="fb-btn fb-btn-danger">
                  <i class="fa-regular fa-trash-can"></i> Hapus
                </button>
              </form>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  </div>
@endsection
