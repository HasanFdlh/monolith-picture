@extends('layouts.app')

@section('title', 'Area Foto - ' . $frame['name'])

@section('content')
  @php
    $aspect = $background->aspect() ?: $frame['aspect'];
    $area = $background->area;
  @endphp

  <div class="frame-shell">
    <div class="upload-panel">
      <h2>Area foto &mdash; {{ $frame['name'] }}</h2>
      <p>
        Geser kotak oranye ke bagian background yang boleh diisi foto, lalu tarik kotak hijau di sudut
        kanan-bawah untuk mengubah ukurannya. Logo, tulisan, dan hiasan di luar kotak tetap terlihat.
        Kotak ini menunjukkan <strong>satu strip</strong>; pada mode &ldquo;Foto Double&rdquo; aplikasi
        menaruh dua strip berdampingan seperti contoh photobooth.
      </p>
    </div>

    <div class="fa-wrap">
      <div class="fa-stage">
        <div class="fa-canvas" id="faCanvas"
          style="--aspect: {{ round($aspect, 4) }}; background-image: url('{{ $background->image_url }}')">
          <div class="fa-area" id="faArea">
            <div class="fa-cells" id="faCells"
              style="grid-template-columns: repeat({{ $frame['cols'] }}, 1fr);
                grid-template-rows: repeat({{ $frame['rows'] }}, 1fr);">
              @for ($i = 1; $i <= $frame['cols'] * $frame['rows']; $i++)
                <span>{{ $i }}</span>
              @endfor
            </div>
            <i class="fa-handle" id="faHandle"></i>
          </div>
        </div>
      </div>

      <form class="fa-panel" id="faForm" method="POST" action="{{ route('frame-backgrounds.area', $frame['key']) }}">
        @csrf
        <h4>Posisi &amp; ukuran (%)</h4>
        <label>Kiri (X) <input type="number" step="0.1" min="0" max="95" name="area_x" id="areaX"></label>
        <label>Atas (Y) <input type="number" step="0.1" min="0" max="95" name="area_y" id="areaY"></label>
        <label>Lebar <input type="number" step="0.1" min="5" max="100" name="area_w" id="areaW"></label>
        <label>Tinggi <input type="number" step="0.1" min="5" max="100" name="area_h" id="areaH"></label>

        <button type="submit" class="fb-btn">Simpan area foto</button>
        <button type="button" class="fb-btn fb-btn-light" id="faReset">Reset</button>
        <a href="{{ route('frame-backgrounds.index') }}" class="fb-btn fb-btn-light">Batal</a>
      </form>
    </div>
  </div>

  <script>
    (() => {
      const canvas = document.getElementById('faCanvas');
      const box = document.getElementById('faArea');
      const handle = document.getElementById('faHandle');
      const inputs = {
        x: document.getElementById('areaX'),
        y: document.getElementById('areaY'),
        w: document.getElementById('areaW'),
        h: document.getElementById('areaH')
      };
      const initial = @json($area);
      const defaults = @json(\App\Models\FrameBackground::DEFAULT_AREA);
      let area = { ...initial };
      let drag = null;
      const clamp = (v, min, max) => Math.max(min, Math.min(v, max));

      const paint = () => {
        box.style.left = `${area.x}%`;
        box.style.top = `${area.y}%`;
        box.style.width = `${area.w}%`;
        box.style.height = `${area.h}%`;
        for (const k of ['x', 'y', 'w', 'h']) inputs[k].value = Number(area[k]).toFixed(1);
      };

      const start = (type) => (event) => {
        drag = { type, x: event.clientX, y: event.clientY, from: { ...area } };
        event.target.setPointerCapture?.(event.pointerId);
        event.preventDefault();
        event.stopPropagation();
      };
      box.addEventListener('pointerdown', start('move'));
      handle.addEventListener('pointerdown', start('resize'));

      window.addEventListener('pointermove', (event) => {
        if (!drag) return;
        const rect = canvas.getBoundingClientRect();
        const dx = (event.clientX - drag.x) / rect.width * 100;
        const dy = (event.clientY - drag.y) / rect.height * 100;
        if (drag.type === 'move') {
          area.x = clamp(drag.from.x + dx, 0, 100 - area.w);
          area.y = clamp(drag.from.y + dy, 0, 100 - area.h);
        } else {
          area.w = clamp(drag.from.w + dx, 5, 100 - area.x);
          area.h = clamp(drag.from.h + dy, 5, 100 - area.y);
        }
        paint();
      });
      window.addEventListener('pointerup', () => drag = null);

      // Ketik angka manual -> kotak ikut berubah (tetap di dalam frame).
      const place = () => {
        box.style.left = `${area.x}%`;
        box.style.top = `${area.y}%`;
        box.style.width = `${area.w}%`;
        box.style.height = `${area.h}%`;
      };
      for (const k of ['x', 'y', 'w', 'h']) {
        inputs[k].addEventListener('input', () => {
          const v = Number(inputs[k].value);
          if (Number.isNaN(v)) return;
          area[k] = v;
          area.w = clamp(area.w, 5, 100);
          area.h = clamp(area.h, 5, 100);
          area.x = clamp(area.x, 0, 100 - area.w);
          area.y = clamp(area.y, 0, 100 - area.h);
          place();
        });
      }

      document.getElementById('faReset').onclick = () => { area = { ...defaults }; paint(); };
      paint();
    })();
  </script>
@endsection
