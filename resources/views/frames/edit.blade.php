@extends('layouts.app')

@section('title', 'Edit Photo Frame Layout')

@section('content')
  <div class="layout-editor-shell">
    <aside class="editor-sidebar">
      <p class="editor-eyebrow">Template builder</p>
      <h3>{{ $frame->name }}</h3>
      <p class="editor-help">Tambah area foto, lalu geser atau tarik sudutnya. Semua ukuran disimpan dalam persentase, jadi
        layout tetap proporsional pada ukuran print apa pun.</p>

      <div class="editor-controls">
        <label for="slotLabel">Slot terpilih</label>
        <input id="slotLabel" class="editor-text-input" type="text" placeholder="Pilih slot terlebih dahulu">
        <div class="editor-button-grid">
          <button type="button" class="editor-action" id="addSlot">+ Tambah slot</button>
          <button type="button" class="editor-action editor-action-muted" id="duplicateSlot">Duplikat</button>
          <button type="button" class="editor-action editor-action-danger" id="deleteSlot">Hapus</button>
        </div>
      </div>

      <div class="editor-controls">
        <label>Rasio slot</label>
        <div class="editor-button-grid ratio-buttons">
          <button type="button" class="editor-action editor-action-muted" data-ratio="1">1 : 1</button>
          <button type="button" class="editor-action editor-action-muted" data-ratio="1.3333">4 : 3</button>
          <button type="button" class="editor-action editor-action-muted" data-ratio="1.7778">16 : 9</button>
        </div>
      </div>

      <div class="editor-controls">
        <label>Ukuran frame <span id="canvasSize"></span></label>
        <p class="editor-shortcut">Tarik sisi kanan, sisi bawah, atau kotak hijau di sudut kanan-bawah frame untuk
          mengatur lebar dan tinggi. Posisi slot ikut menyesuaikan secara proporsional.</p>
        <button type="button" class="reset-btn" id="resetCanvasSize">Ukuran asli PNG</button>
      </div>

      <div class="editor-controls">
        <label for="canvasZoom">Zoom <span id="zoomValue">100%</span></label>
        <input id="canvasZoom" type="range" min="60" max="140" value="100">
        <button type="button" class="reset-btn" id="resetLayout">Reset layout</button>
      </div>

      <p class="editor-shortcut">Tip: klik slot untuk memilihnya. Drag bagian tengah untuk memindahkan, atau tarik handle
        kanan-bawah untuk mengubah ukuran.</p>
    </aside>

    <div class="editor-canvas-wrap">
      <div class="editor-toolbar">
        <span id="slotCount">0 photo slots</span>
        <span class="toolbar-hint">Klik slot, lalu masukkan foto test</span>
        <input id="testPhotos" type="file" accept="image/png,image/jpeg,image/webp" multiple hidden>
        <button type="button" class="toolbar-btn" id="chooseTestPhotos">Masukkan foto test</button>
        <button type="button" class="toolbar-btn" id="previewResult">Preview hasil</button>
        <button type="button" class="toolbar-btn toolbar-print-btn" id="printResult">Print</button>
      </div>
      <div class="editor-stage" id="editorStage">
        <div class="editor-canvas" id="editorCanvas"
          style="--frame-image: url('{{ asset('storage/' . $frame->file_path) }}')">
          <div class="layout-grid" id="layoutGrid"></div>
          <span class="canvas-handle canvas-handle-right" data-canvas-handle="right" title="Tarik untuk ubah lebar"></span>
          <span class="canvas-handle canvas-handle-bottom" data-canvas-handle="bottom" title="Tarik untuk ubah tinggi"></span>
          <span class="canvas-handle canvas-handle-corner" data-canvas-handle="both" title="Tarik untuk ubah lebar & tinggi"></span>
        </div>
      </div>
    </div>
  </div>

  <form id="frameLayoutForm" action="{{ route('frames.update', $frame) }}" method="POST">
    @csrf
    @method('PUT')
    <input type="hidden" name="layout_json" id="layout_json"
      value="{{ old(
          'layout_json',
          json_encode(
              $frame->layout_json ?? [
                  'width' => \App\Models\PhotoFrame::DEFAULT_WIDTH,
                  'height' => \App\Models\PhotoFrame::DEFAULT_HEIGHT,
                  'slots' => [],
              ],
          ),
      ) }}">
    <input type="hidden" name="name" value="{{ old('name', $frame->name) }}">
    <input type="hidden" name="status" value="{{ old('status', $frame->status ?? 'active') }}">
    <input type="hidden" name="category" value="{{ old('category', $frame->category ?? 'All') }}">
    <input type="hidden" name="scope" value="{{ old('scope', $frame->scope ?? 'Generic') }}">
    <input type="hidden" name="print_size" value="{{ old('print_size', $frame->print_size ?? 'Normal') }}">
    <input type="hidden" name="printer_setting"
      value="{{ old('printer_setting', $frame->printer_setting ?? 'Primary Printer') }}">
    <div class="save-layout-row"><a href="{{ route('frames.index') }}" class="cancel-layout-btn">Batal</a><button
        type="submit" class="save-layout-btn">Simpan layout</button></div>
  </form>

  <script>
    (() => {
      const grid = document.getElementById('layoutGrid'),
        formValue = document.getElementById('layout_json');
      const labelInput = document.getElementById('slotLabel'),
        count = document.getElementById('slotCount');
      const frameSource = @json(asset('storage/' . $frame->file_path));
      let layout;
      try {
        layout = JSON.parse(formValue.value);
      } catch (_) {
        layout = {};
      }
      // Ukuran yang sudah tersimpan tidak boleh ditimpa ukuran PNG saat dibuka.
      const hasSavedSize = Number(layout.width) > 0 && Number(layout.height) > 0;
      layout = {
        width: Number(layout.width) || {{ \App\Models\PhotoFrame::DEFAULT_WIDTH }},
        height: Number(layout.height) || {{ \App\Models\PhotoFrame::DEFAULT_HEIGHT }},
        slots: Array.isArray(layout.slots) ? layout.slots : []
      };
      const legacyGrid = layout.slots.length && layout.slots.every(slot => Number(slot.w) <= 1 && Number(slot.h) <= 1);
      // Template lama (grid 2x2) disusun ulang menjadi strip vertikal:
      // slot bertumpuk dari atas ke bawah, sisa bawah untuk branding.
      const stackTop = 4, stackBottom = 14, stackGap = 2;
      const stackHeight = layout.slots.length
        ? (100 - stackTop - stackBottom - stackGap * (layout.slots.length - 1)) / layout.slots.length
        : 0;
      layout.slots = layout.slots.map((slot, i) => ({
        id: slot.id || `slot-${Date.now()}-${i}`,
        label: slot.label || `Foto ${i + 1}`,
        x: legacyGrid ? 8 : Number(slot.x) || 10,
        y: legacyGrid ? stackTop + i * (stackHeight + stackGap) : Number(slot.y) || 10,
        w: legacyGrid ? 84 : Number(slot.w) || 35,
        h: legacyGrid ? stackHeight : Number(slot.h) || 35
      }));
      // Rasio kanvas langsung mengikuti layout (vertikal bila tinggi > lebar),
      // tidak menunggu PNG frame selesai dimuat.
      const canvasEl = document.getElementById('editorCanvas');
      const canvasSizeLabel = document.getElementById('canvasSize');
      // view = ukuran kanvas di layar (px CSS); layout.width/height = ukuran cetak.
      const view = { w: 0, h: 0 };
      const paintCanvas = () => {
        canvasEl.style.aspectRatio = 'auto';
        canvasEl.style.width = `${view.w}px`;
        canvasEl.style.height = `${view.h}px`;
        canvasSizeLabel.textContent = `${layout.width} × ${layout.height} px`;
      };
      const fitCanvas = () => {
        view.w = layout.height > layout.width ? 360 : 820;
        view.h = view.w * layout.height / layout.width;
        paintCanvas();
      };
      fitCanvas();
      // Drag sisi / sudut frame untuk mengubah lebar dan tinggi.
      let canvasDrag = null;
      document.querySelectorAll('[data-canvas-handle]').forEach(handle => {
        handle.addEventListener('pointerdown', event => {
          canvasDrag = {
            mode: handle.dataset.canvasHandle,
            startX: event.clientX,
            startY: event.clientY,
            w: view.w,
            h: view.h,
            scale: layout.width / view.w
          };
          handle.setPointerCapture(event.pointerId);
          event.preventDefault();
          event.stopPropagation();
        });
      });
      window.addEventListener('pointermove', event => {
        if (!canvasDrag) return;
        const zoom = (Number(document.getElementById('canvasZoom').value) || 100) / 100;
        const dx = (event.clientX - canvasDrag.startX) / zoom;
        const dy = (event.clientY - canvasDrag.startY) / zoom;
        if (canvasDrag.mode !== 'bottom') view.w = Math.max(120, Math.min(canvasDrag.w + dx, 1400));
        if (canvasDrag.mode !== 'right') view.h = Math.max(120, Math.min(canvasDrag.h + dy, 4000));
        layout.width = Math.max(1, Math.round(view.w * canvasDrag.scale));
        layout.height = Math.max(1, Math.round(view.h * canvasDrag.scale));
        paintCanvas();
        sync();
      });
      window.addEventListener('pointerup', () => canvasDrag = null);
      let selectedId = layout.slots[0]?.id || null,
        interaction = null;
      const testPhotos = new Map();
      const clamp = (value, min, max) => Math.max(min, Math.min(value, max));
      const selected = () => layout.slots.find(slot => slot.id === selectedId);
      const sync = () => {
        formValue.value = JSON.stringify(layout);
        count.textContent = `${layout.slots.length} photo slot${layout.slots.length === 1 ? '' : 's'}`;
        labelInput.value = selected()?.label || '';
        labelInput.disabled = !selected();
      };
      const render = () => {
        grid.innerHTML = '';
        layout.slots.forEach((slot, index) => {
          const element = document.createElement('button');
          element.type = 'button';
          element.className = `slot-box${slot.id === selectedId ? ' is-selected' : ''}`;
          element.dataset.id = slot.id;
          element.style.left = `${slot.x}%`;
          element.style.top = `${slot.y}%`;
          element.style.width = `${slot.w}%`;
          element.style.height = `${slot.h}%`;
          if (testPhotos.has(slot.id)) element.style.backgroundImage = `url("${testPhotos.get(slot.id)}")`;
          element.innerHTML =
            `<span>${slot.label}</span><small>${testPhotos.has(slot.id) ? 'Foto test' : `Foto ${index + 1}`}</small><i class="slot-resize" aria-hidden="true"></i>`;
          element.addEventListener('pointerdown', startInteraction);
          grid.appendChild(element);
        });
        sync();
      };
      const startInteraction = (event) => {
        const id = event.currentTarget.dataset.id;
        selectedId = id;
        const slot = selected();
        interaction = {
          type: event.target.closest('.slot-resize') ? 'resize' : 'move',
          startX: event.clientX,
          startY: event.clientY,
          initial: {
            ...slot
          }
        };
        event.currentTarget.setPointerCapture(event.pointerId);
        render();
        event.preventDefault();
      };
      window.addEventListener('pointermove', event => {
        if (!interaction || !selected()) return;
        const rect = grid.getBoundingClientRect(),
          slot = selected();
        const dx = ((event.clientX - interaction.startX) / rect.width) * 100,
          dy = ((event.clientY - interaction.startY) / rect.height) * 100;
        if (interaction.type === 'move') {
          slot.x = clamp(interaction.initial.x + dx, 0, 100 - slot.w);
          slot.y = clamp(interaction.initial.y + dy, 0, 100 - slot.h);
        } else {
          slot.w = clamp(interaction.initial.w + dx, 8, 100 - slot.x);
          slot.h = clamp(interaction.initial.h + dy, 8, 100 - slot.y);
        }
        render();
      });
      window.addEventListener('pointerup', () => interaction = null);
      document.getElementById('addSlot').onclick = () => {
        const n = layout.slots.length + 1;
        const slot = {
          id: `slot-${Date.now()}`,
          label: `Foto ${n}`,
          x: 12 + ((n - 1) * 5) % 35,
          y: 12 + ((n - 1) * 5) % 35,
          w: 35,
          h: 35
        };
        layout.slots.push(slot);
        selectedId = slot.id;
        render();
      };
      document.getElementById('duplicateSlot').onclick = () => {
        const slot = selected();
        if (!slot) return;
        const copy = {
          ...slot,
          id: `slot-${Date.now()}`,
          label: `${slot.label} copy`,
          x: clamp(slot.x + 4, 0, 100 - slot.w),
          y: clamp(slot.y + 4, 0, 100 - slot.h)
        };
        layout.slots.push(copy);
        selectedId = copy.id;
        render();
      };
      document.getElementById('deleteSlot').onclick = () => {
        if (!selected()) return;
        layout.slots = layout.slots.filter(slot => slot.id !== selectedId);
        selectedId = layout.slots[0]?.id || null;
        render();
      };
      labelInput.oninput = () => {
        if (selected()) {
          selected().label = labelInput.value.slice(0, 50);
          render();
        }
      };
      document.querySelectorAll('[data-ratio]').forEach(button => button.onclick = () => {
        const slot = selected();
        if (!slot) return;
        const ratio = Number(button.dataset.ratio);
        slot.h = clamp(slot.w / ratio, 8, 100 - slot.y);
        render();
      });
      document.getElementById('canvasZoom').oninput = event => {
        document.getElementById('editorCanvas').style.transform = `scale(${event.target.value / 100})`;
        document.getElementById('zoomValue').textContent = `${event.target.value}%`;
      };
      document.getElementById('resetLayout').onclick = () => {
        if (confirm('Hapus semua slot dari layout ini?')) {
          layout.slots = [];
          selectedId = null;
          render();
        }
      };
      document.getElementById('chooseTestPhotos').onclick = () => document.getElementById('testPhotos').click();
      document.getElementById('testPhotos').onchange = event => {
        const files = [...event.target.files];
        if (files.length === 1) {
          // A single image replaces only the currently selected slot.
          if (selectedId) {
            const previous = testPhotos.get(selectedId);
            if (previous) URL.revokeObjectURL(previous);
            testPhotos.set(selectedId, URL.createObjectURL(files[0]));
          }
        } else {
          [...new Set(testPhotos.values())].forEach(url => URL.revokeObjectURL(url));
          testPhotos.clear();
          files.slice(0, layout.slots.length).forEach((file, index) => testPhotos.set(layout.slots[index].id, URL.createObjectURL(file)));
        }
        event.target.value = '';
        render();
      };
      let naturalSize = null;
      const loadImage = source => new Promise((resolve, reject) => {
        const image = new Image(); image.onload = () => resolve(image); image.onerror = reject; image.src = source;
      });
      loadImage(frameSource).then(image => {
        // The original template dimensions define the print canvas. This keeps
        // slot coordinates identical between the editor, preview, and print.
        naturalSize = { width: image.naturalWidth, height: image.naturalHeight };
        if (!hasSavedSize) {
          layout.width = naturalSize.width;
          layout.height = naturalSize.height;
          fitCanvas();
        }
        sync();
      }).catch(() => {});
      document.getElementById('resetCanvasSize').onclick = () => {
        if (!naturalSize) { alert('PNG frame belum tersedia, ukuran asli tidak diketahui.'); return; }
        layout.width = naturalSize.width;
        layout.height = naturalSize.height;
        fitCanvas();
        sync();
      };
      const renderFinal = async () => {
        if (!layout.slots.length) throw new Error('Tambahkan minimal satu slot terlebih dahulu.');
        if (!testPhotos.size) throw new Error('Masukkan foto test terlebih dahulu.');
        const output = document.createElement('canvas'); output.width = layout.width; output.height = layout.height;
        const context = output.getContext('2d'); context.fillStyle = '#fff'; context.fillRect(0, 0, output.width, output.height);
        for (const slot of layout.slots) {
          const source = testPhotos.get(slot.id); if (!source) continue;
          const image = await loadImage(source), x = output.width * slot.x / 100, y = output.height * slot.y / 100, w = output.width * slot.w / 100, h = output.height * slot.h / 100;
          const scale = Math.max(w / image.width, h / image.height), sw = w / scale, sh = h / scale;
          context.drawImage(image, (image.width - sw) / 2, (image.height - sh) / 2, sw, sh, x, y, w, h);
        }
        try { context.drawImage(await loadImage(frameSource), 0, 0, output.width, output.height); } catch (_) {}
        return output.toDataURL('image/png');
      };
      const showFinal = async print => {
        try {
          const image = await renderFinal(), popup = window.open('', '_blank');
          if (!popup) throw new Error('Izinkan pop-up untuk membuka preview atau print.');
          popup.document.write(`<!doctype html><title>Photobooth result</title><style>body{margin:0;background:#111;display:grid;place-items:center;min-height:100vh}img{max-width:100%;max-height:100vh}@media print{body{background:#fff}}</style><img src="${image}">`);
          popup.document.close(); if (print) popup.onload = () => { popup.focus(); popup.print(); };
        } catch (error) { alert(error.message || 'Preview gagal dibuat.'); }
      };
      document.getElementById('previewResult').onclick = () => showFinal(false);
      document.getElementById('printResult').onclick = () => showFinal(true);
      document.getElementById('frameLayoutForm').onsubmit = () => {
        sync();
      };
      render();
    })();
  </script>
@endsection
