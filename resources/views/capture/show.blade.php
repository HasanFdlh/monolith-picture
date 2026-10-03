@extends('layouts.kiosk')

@section('title', $title)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="h5 kiosk-brand mb-0">Sesi Foto</div>
            <small class="text-muted-2">Kode: {{ $session->session_code }} · Paket:
                {{ $session->photoPackage->name ?? '-' }}</small>
        </div>
        <div class="text-end">
            <div class="text-muted-2 small">Sisa take</div>
            <div class="h4 mb-0 text-info" id="remaining-takes">{{ $remainingTakes }}</div>
        </div>
    </div>

    <div class="panel p-4" id="capture-panel">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">Template</label>
                <select id="template" class="form-select" required>
                    <option value="">Memuat template...</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Orientasi</label>
                <select id="orientation" class="form-select">
                    <option value="portrait">Portrait</option>
                    <option value="landscape">Landscape</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Grid</label>
                <input id="grid" class="form-control" readonly value="Pilih template dulu">
            </div>
            <div class="col-12">
                <label id="photo-label" class="form-label small">Foto per slot</label>
                <div id="photo-picker" class="photo-picker text-muted-2">Pilih template untuk menampilkan slot foto.
                </div>
                <div id="photo-summary" class="form-text"></div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button id="save-button" class="btn btn-primary" type="button">Simpan Foto</button>
            <button id="reset-button" class="btn btn-outline-secondary" type="button">Reset Pilihan</button>
        </div>
    </div>

    <div id="review-card" class="panel p-4 mt-4 d-none">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="h6 mb-0">Review hasil</div>
                <small id="review-caption" class="text-muted-2"></small>
            </div>
            <span class="badge text-bg-info" id="review-grid"></span>
        </div>
        <div id="review-canvas" class="review-canvas portrait"></div>
    </div>

    <div id="status" class="alert d-none mt-3" role="alert"></div>

    <div id="finish-panel" class="panel p-4 mt-4 d-none">
        <div class="h6">Sesi selesai</div>
        <p class="text-muted-2 small mb-2">Semua take sudah terpakai. Kirim hasil ke email kalau perlu.</p>
        <div class="row g-2">
            <div class="col-md-8"><input id="email" type="email" class="form-control" placeholder="email@contoh.com">
            </div>
            <div class="col-md-4"><button id="email-button" class="btn btn-success w-100" type="button">Kirim &
                    Print</button></div>
        </div>
    </div>

    @push('styles')
        <style>
            .photo-picker-input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
            .photo-picker { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .75rem; }
            .photo-slot-picker { border: 1px solid #334155; border-radius: .5rem; padding: .7rem; background: #0f172a; }
            .review-canvas { position: relative; width: min(100%, 620px); margin: auto; overflow: hidden; background: #e9ecef; box-shadow: inset 0 0 0 1px #ced4da; }
            .review-canvas.portrait { aspect-ratio: 3 / 4; }
            .review-canvas.landscape { aspect-ratio: 4 / 3; }
            .review-slot { position: absolute; overflow: hidden; display: grid; place-items: center; color: #6c757d; background: #dfe4ea; border: 1px solid #fff; font-size: .8rem; }
            .review-slot img { width: 100%; height: 100%; object-fit: cover; }
            .frame-overlay { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: fill; pointer-events: none; }
        </style>
    @endpush

    @push('scripts')
        <script>
            (() => {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const captureStoreUrl = @json(route('capture.store', $session));
                const printUrl = @json(url('/api/photo-sessions/' . $session->id . '/print'));
                let remaining = {{ $remainingTakes }};

                const template = document.getElementById('template');
                const grid = document.getElementById('grid');
                const orientation = document.getElementById('orientation');
                const photoPicker = document.getElementById('photo-picker');
                const photoLabel = document.getElementById('photo-label');
                const photoSummary = document.getElementById('photo-summary');
                const status = document.getElementById('status');
                const reviewCard = document.getElementById('review-card');
                const reviewCanvas = document.getElementById('review-canvas');
                const reviewCaption = document.getElementById('review-caption');
                const reviewGrid = document.getElementById('review-grid');
                const saveButton = document.getElementById('save-button');
                const resetButton = document.getElementById('reset-button');
                const remainingEl = document.getElementById('remaining-takes');
                const finishPanel = document.getElementById('finish-panel');
                let templates = [];

                const display = (message, type) => {
                    status.textContent = message;
                    status.className = `alert alert-${type} mt-3`;
                };
                const selectedTemplate = () => templates.find(frame => String(frame.id) === template.value);
                const slotsFor = (frame) => {
                    const raw = frame?.layout_json?.slots || [];
                    const max = Math.max(...raw.map(slot => Math.max(Number(slot.x || 0) + Number(slot.w || 0), Number(slot.y || 0) + Number(slot.h || 0))), 1);
                    const scale = max <= 10 ? 100 / max : 1;
                    return raw.map(slot => ({ x: Number(slot.x || 0) * scale, y: Number(slot.y || 0) * scale, w: Number(slot.w || 0) * scale, h: Number(slot.h || 0) * scale }));
                };
                const gridFor = (frame) => {
                    const slots = frame?.layout_json?.slots || [];
                    if (!slots.length) return 'custom';
                    return `${new Set(slots.map(slot => String(slot.x))).size}x${new Set(slots.map(slot => String(slot.y))).size}`;
                };
                const selectedPhotos = () => Array.from(photoPicker.querySelectorAll('input[type="file"]')).map(input => input.files[0]).filter(Boolean);

                const renderPickers = () => {
                    const frame = selectedTemplate();
                    const slots = slotsFor(frame);
                    photoPicker.replaceChildren();

                    if (!frame) {
                        photoLabel.textContent = 'Foto per slot';
                        photoPicker.textContent = 'Pilih template untuk menampilkan slot foto.';
                        photoSummary.textContent = '';
                        reviewCard.classList.add('d-none');
                        return;
                    }

                    photoLabel.textContent = `${slots.length} foto untuk ${gridFor(frame)}`;
                    photoSummary.textContent = `Sisa take kamu: ${remaining}. Maksimal 10 MB per foto.`;

                    slots.forEach((slot, index) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'photo-slot-picker';
                        const input = document.createElement('input');
                        input.type = 'file';
                        input.accept = 'image/*';
                        input.className = 'photo-picker-input';
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'btn btn-outline-info btn-sm';
                        button.textContent = `Pilih Foto ${index + 1}`;
                        const name = document.createElement('small');
                        name.className = 'text-muted-2 d-block mt-1';
                        name.textContent = 'Belum dipilih';
                        button.addEventListener('click', () => input.click());
                        input.addEventListener('change', () => {
                            name.textContent = input.files[0]?.name || 'Belum dipilih';
                            renderReview();
                        });
                        wrapper.append(input, button, name);
                        photoPicker.append(wrapper);
                    });

                    renderReview();
                };

                const renderReview = () => {
                    const frame = selectedTemplate();
                    const slots = slotsFor(frame);
                    const files = selectedPhotos();
                    if (!frame || !files.length) {
                        reviewCard.classList.add('d-none');
                        return;
                    }
                    const urls = files.map(file => URL.createObjectURL(file));
                    reviewCard.classList.remove('d-none');
                    reviewCanvas.className = `review-canvas ${orientation.value}`;
                    reviewCaption.textContent = `${frame.name} · ${files.length} foto`;
                    reviewGrid.textContent = gridFor(frame);
                    reviewCanvas.replaceChildren();
                    slots.forEach((slot, index) => {
                        const cell = document.createElement('div');
                        cell.className = 'review-slot';
                        Object.assign(cell.style, { left: `${slot.x}%`, top: `${slot.y}%`, width: `${slot.w}%`, height: `${slot.h}%` });
                        if (urls[index]) {
                            const image = new Image();
                            image.src = urls[index];
                            image.alt = `Foto ${index + 1}`;
                            cell.append(image);
                        } else {
                            cell.textContent = `Foto ${index + 1}`;
                        }
                        reviewCanvas.append(cell);
                    });
                    if (frame.file_path) {
                        const overlay = new Image();
                        overlay.className = 'frame-overlay';
                        overlay.src = `/storage/${frame.file_path.replace(/^storage\//, '')}`;
                        overlay.alt = 'Template frame';
                        reviewCanvas.append(overlay);
                    }
                };

                fetch('/api/templates', { headers: { Accept: 'application/json' } })
                    .then(response => response.json())
                    .then(({ data }) => {
                        templates = data;
                        const available = data.filter(frame => (frame.layout_json?.slots || []).length <= remaining);
                        template.innerHTML = available.length
                            ? '<option value="">Pilih template</option>' + available.map(frame => `<option value="${frame.id}">${frame.name} — ${frame.print_size}</option>`).join('')
                            : '<option value="">Tidak ada template yang cocok dengan sisa take</option>';
                    })
                    .catch(error => display(error.message, 'danger'));

                template.addEventListener('change', () => {
                    grid.value = selectedTemplate() ? gridFor(selectedTemplate()) : 'Pilih template dulu';
                    renderPickers();
                });
                orientation.addEventListener('change', renderReview);
                resetButton.addEventListener('click', renderPickers);

                saveButton.addEventListener('click', async () => {
                    const frame = selectedTemplate();
                    const files = selectedPhotos();
                    if (!frame) { display('Pilih template dulu.', 'warning'); return; }

                    const slots = slotsFor(frame);
                    if (files.length !== slots.length) { display(`Lengkapi ${slots.length} foto dulu.`, 'warning'); return; }
                    if (files.length > remaining) { display(`Sisa take kamu hanya ${remaining} foto.`, 'warning'); return; }

                    const formData = new FormData();
                    formData.append('photo_frame_id', frame.id);
                    files.forEach(file => formData.append('photos[]', file));

                    saveButton.disabled = true;
                    try {
                        const response = await fetch(captureStoreUrl, {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: formData,
                        });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Gagal menyimpan foto.');

                        remaining = data.remaining_takes;
                        remainingEl.textContent = remaining;
                        display(`${data.message} Sisa take: ${remaining}.`, 'success');

                        template.value = '';
                        grid.value = 'Pilih template dulu';
                        photoPicker.textContent = 'Pilih template untuk menampilkan slot foto.';
                        photoSummary.textContent = '';
                        reviewCard.classList.add('d-none');

                        if (remaining <= 0) {
                            finishPanel.classList.remove('d-none');
                        }
                    } catch (error) {
                        display(error.message, 'danger');
                    } finally {
                        saveButton.disabled = remaining <= 0;
                    }
                });

                document.getElementById('email-button')?.addEventListener('click', async event => {
                    const email = document.getElementById('email').value;
                    if (!email) { display('Isi email dulu.', 'warning'); return; }
                    event.currentTarget.disabled = true;
                    try {
                        const response = await fetch(printUrl, {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email }),
                        });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'Gagal mengirim email.');
                        display(data.message, 'success');
                    } catch (error) {
                        display(error.message, 'danger');
                    } finally {
                        event.currentTarget.disabled = false;
                    }
                });
            })();
        </script>
    @endpush
@endsection
