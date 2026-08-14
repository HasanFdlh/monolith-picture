@extends('layouts.app')

@section('title', 'Photobooth API Test')

@section('content')
    <div class="card border-0 shadow-sm rounded-4 mt-3">
        <div class="card-body p-4">
            <h4 class="mb-1">Photobooth flow test</h4>
            <p class="text-muted mb-4">Template → browse foto sebagai simulasi capture → pilih orientasi → review → simpan → print & email.</p>

            <form id="photobooth-form" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Booth</label>
                    <select name="booth_id" class="form-select" required>
                        <option value="">Pilih booth</option>
                        @foreach ($booths as $booth)
                            <option value="{{ $booth->id }}">{{ $booth->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama customer</label>
                    <input name="customer_name" class="form-control" placeholder="Opsional">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Template samping</label>
                    <select id="template" name="photo_frame_id" class="form-select" required>
                        <option value="">Memuat template...</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Orientasi foto</label>
                    <select id="orientation" name="layout" class="form-select"><option value="portrait">Portrait</option><option value="landscape">Landscape</option></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Grid dari template</label>
                    <input id="grid" name="grid" class="form-control" readonly value="Pilih template dulu">
                </div>
                <div class="col-12">
                    <label id="photo-label" class="form-label">Foto per slot</label>
                    <div id="photo-picker" class="photo-picker text-muted">Pilih template untuk menampilkan slot foto.</div>
                    <div id="photo-summary" class="form-text mt-2"></div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Simpan Session</button>
                    <button id="print-button" class="btn btn-success" type="button" disabled>Print & Kirim Email</button>
                </div>
            </form>
        </div>
    </div>

    <div id="review-card" class="card border-0 shadow-sm rounded-4 mt-4 d-none">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div><h5 class="mb-0">Review hasil</h5><small id="review-caption" class="text-muted"></small></div>
                <span class="badge text-bg-primary" id="review-grid"></span>
            </div>
            <div id="review-canvas" class="review-canvas portrait"></div>
        </div>
    </div>

    <div id="status" class="alert d-none mt-3" role="alert"></div>
    <pre id="result" class="bg-dark text-light rounded-4 p-3 mt-3 d-none" style="white-space:pre-wrap"></pre>

    <script>
        (() => {
            const form = document.getElementById('photobooth-form');
            const photoPicker = document.getElementById('photo-picker');
            const photoLabel = document.getElementById('photo-label');
            const photoSummary = document.getElementById('photo-summary');
            const template = document.getElementById('template');
            const status = document.getElementById('status');
            const result = document.getElementById('result');
            const printButton = document.getElementById('print-button');
            const orientation = document.getElementById('orientation');
            const grid = document.getElementById('grid');
            const reviewCard = document.getElementById('review-card');
            const reviewCanvas = document.getElementById('review-canvas');
            const reviewCaption = document.getElementById('review-caption');
            const reviewGrid = document.getElementById('review-grid');
            let sessionId = null;
            let templates = [];

            const display = (message, type, data = null) => {
                status.textContent = message;
                status.className = `alert alert-${type} mt-3`;
                result.classList.toggle('d-none', !data);
                if (data) result.textContent = JSON.stringify(data, null, 2);
            };
            const api = async (url, options = {}) => {
                const response = await fetch(`/api${url}`, { headers: { Accept: 'application/json', ...(options.headers || {}) }, ...options });
                const body = await response.json();
                if (!response.ok) throw new Error(body.message || Object.values(body.errors || {}).flat().join(' ') || 'Request gagal');
                return body;
            };

            const selectedTemplate = () => templates.find(frame => String(frame.id) === template.value);
            const slotsFor = (frame) => {
                const raw = frame?.layout_json?.slots || [];
                const max = Math.max(...raw.map(slot => Math.max(Number(slot.x || 0) + Number(slot.w || 0), Number(slot.y || 0) + Number(slot.h || 0))), 1);
                const scale = max <= 10 ? 100 / max : 1; // support template lama yang memakai koordinat 0, 1, 2
                return raw.map(slot => ({ x: Number(slot.x || 0) * scale, y: Number(slot.y || 0) * scale, w: Number(slot.w || 0) * scale, h: Number(slot.h || 0) * scale }));
            };
            const gridFor = (frame) => {
                const slots = frame?.layout_json?.slots || [];
                if (!slots.length) return 'custom';
                return `${new Set(slots.map(slot => String(slot.x))).size}x${new Set(slots.map(slot => String(slot.y))).size}`;
            };
            const selectedPhotos = () => Array.from(photoPicker.querySelectorAll('input[type="file"]')).map(input => input.files[0]).filter(Boolean);
            const renderPickers = () => {
                const slots = slotsFor(selectedTemplate());
                photoPicker.replaceChildren();
                photoLabel.textContent = slots.length ? `${slots.length} foto untuk ${gridFor(selectedTemplate())}` : 'Foto per slot';
                photoSummary.textContent = slots.length ? 'Pilih satu foto untuk setiap slot. Maksimal 10 MB per foto.' : '';
                if (!slots.length) { photoPicker.textContent = 'Pilih template untuk menampilkan slot foto.'; return; }
                slots.forEach((slot, index) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'photo-slot-picker';
                    const input = document.createElement('input');
                    input.type = 'file'; input.name = 'photos[]'; input.accept = 'image/*'; input.className = 'photo-picker-input';
                    const button = document.createElement('button');
                    button.type = 'button'; button.className = 'btn btn-outline-primary btn-sm'; button.textContent = `Pilih Foto ${index + 1}`;
                    const name = document.createElement('small'); name.className = 'text-muted d-block mt-1'; name.textContent = 'Belum dipilih';
                    button.addEventListener('click', () => input.click());
                    input.addEventListener('change', () => { name.textContent = input.files[0]?.name || 'Belum dipilih'; renderReview(); });
                    wrapper.append(input, button, name);
                    photoPicker.append(wrapper);
                });
            };
            const renderReview = () => {
                const frame = selectedTemplate();
                const slots = slotsFor(frame);
                const files = selectedPhotos();
                if (!frame || !files.length || !slots.length) { reviewCard.classList.add('d-none'); return; }
                const urls = files.map(file => URL.createObjectURL(file));
                reviewCard.classList.remove('d-none');
                reviewCanvas.className = `review-canvas ${orientation.value}`;
                reviewCaption.textContent = `${frame.name} · ${files.length} foto dipreview`;
                reviewGrid.textContent = gridFor(frame);
                reviewCanvas.replaceChildren();
                slots.forEach((slot, index) => {
                    const cell = document.createElement('div');
                    cell.className = 'review-slot';
                    Object.assign(cell.style, { left: `${slot.x}%`, top: `${slot.y}%`, width: `${slot.w}%`, height: `${slot.h}%` });
                    if (urls[index]) { const image = new Image(); image.src = urls[index]; image.alt = `Foto ${index + 1}`; cell.append(image); }
                    else cell.textContent = `Foto ${index + 1}`;
                    reviewCanvas.append(cell);
                });
                if (frame.file_path) { const overlay = new Image(); overlay.className = 'frame-overlay'; overlay.src = `/storage/${frame.file_path.replace(/^storage\//, '')}`; overlay.alt = 'Template frame'; reviewCanvas.append(overlay); }
            };

            api('/templates').then(({ data }) => {
                templates = data;
                template.innerHTML = '<option value="">Pilih template</option>' + data.map(frame => `<option value="${frame.id}">${frame.name} — ${frame.print_size}</option>`).join('');
            }).catch(error => display(error.message, 'danger'));

            template.addEventListener('change', () => { grid.value = selectedTemplate() ? gridFor(selectedTemplate()) : 'Pilih template dulu'; renderPickers(); renderReview(); });
            orientation.addEventListener('change', renderReview);

            form.addEventListener('submit', async event => {
                event.preventDefault();
                const slots = slotsFor(selectedTemplate());
                const photos = selectedPhotos();
                if (photos.length !== slots.length) { display(`Lengkapi semua ${slots.length} foto sesuai slot template dulu.`, 'warning'); return; }
                try {
                    const data = await api('/photo-sessions', { method: 'POST', body: new FormData(form) });
                    sessionId = data.data.id;
                    printButton.disabled = false;
                    display(`Session ${data.data.session_code} tersimpan. ${data.data.total_files} file / ${data.data.total_size} bytes.`, 'success', data);
                } catch (error) { display(error.message, 'danger'); }
            });

            printButton.addEventListener('click', async () => {
                const email = prompt('Masukkan email untuk pengiriman foto:');
                if (!email) return;
                try {
                    const data = await api(`/photo-sessions/${sessionId}/print`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email }) });
                    display(data.message, 'success', data);
                } catch (error) { display(error.message, 'danger'); }
            });
        })();
    </script>
    <style>
        .photo-picker-input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
        .photo-picker { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .75rem; }
        .photo-slot-picker { border: 1px solid #dee2e6; border-radius: .5rem; padding: .7rem; background: #fff; }
        .review-canvas { position: relative; width: min(100%, 620px); margin: auto; overflow: hidden; background: #e9ecef; box-shadow: inset 0 0 0 1px #ced4da; }
        .review-canvas.portrait { aspect-ratio: 3 / 4; }
        .review-canvas.landscape { aspect-ratio: 4 / 3; }
        .review-slot { position: absolute; overflow: hidden; display: grid; place-items: center; color: #6c757d; background: #dfe4ea; border: 1px solid #fff; font-size: .8rem; }
        .review-slot img { width: 100%; height: 100%; object-fit: cover; }
        .frame-overlay { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: fill; pointer-events: none; }
    </style>
@endsection
