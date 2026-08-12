@extends('layouts.app')

@section('title', 'Edit Photo Frame Layout')

@section('content')
    <div class="layout-editor-shell">
        <aside class="editor-sidebar">
            <h3>Frame Layout Editor</h3>
            <ol>
                <li>Draw or add rectangles for each photo slot.</li>
                <li>Use quick aspect ratio buttons for 4:3 or 16:9.</li>
                <li>Resize, move, or duplicate rectangles for uniformity.</li>
                <li>Save the layout and rename your frame.</li>
            </ol>

            <div class="editor-controls">
                <label>Zoom</label>
                <div class="zoom-bar">
                    <input type="range" min="50" max="150" value="100" />
                    <span>100%</span>
                </div>
                <button type="button" class="reset-btn">Reset Fit</button>
            </div>

            <div class="color-panel">
                <label>Auto-Detect Slots</label>
                <p>Pick a color for the frame to auto-detect slots.</p>
                <div class="color-row-editor">
                    <span class="color-swatch">#00FF00</span>
                    <button type="button" class="pick-color-btn">Pick Color</button>
                </div>
            </div>

            <div class="tolerance-panel">
                <label>Tolerance</label>
                <input type="range" min="0" max="20" value="0" />
            </div>

            <button type="button" class="auto-detect-btn">Auto-Detect Slots</button>
            <button type="button" class="auto-detect-btn secondary">Apply Layout</button>
        </aside>

        <div class="editor-canvas-wrap">
            <div class="editor-toolbar">
                <button type="button" class="toolbar-btn small active">Rect</button>
                <button type="button" class="toolbar-btn small">Move</button>
                <button type="button" class="toolbar-btn small">Delete</button>
            </div>

            <div class="editor-canvas" id="editorCanvas">
                <div class="layout-grid" id="layoutGrid"></div>
            </div>
        </div>
    </div>

    <form id="frameLayoutForm" action="{{ route('frames.update', $frame) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <input type="hidden" name="layout_json" id="layout_json" value='{{ old('layout_json', $frame->layout_json ? json_encode($frame->layout_json) : json_encode(['slots' => []])) }}'>
        <input type="hidden" name="name" value="{{ old('name', $frame->name) }}">
        <input type="hidden" name="status" value="{{ old('status', $frame->status ?? 'active') }}">
        <input type="hidden" name="category" value="{{ old('category', $frame->category ?? 'All') }}">
        <input type="hidden" name="scope" value="{{ old('scope', $frame->scope ?? 'Generic') }}">
        <input type="hidden" name="print_size" value="{{ old('print_size', $frame->print_size ?? 'Normal') }}">
        <input type="hidden" name="printer_setting" value="{{ old('printer_setting', $frame->printer_setting ?? 'Primary Printer') }}">

        <div class="save-layout-row">
            <button type="submit" class="save-layout-btn">Save Layout</button>
        </div>
    </form>

    <script>
        const canvas = document.getElementById('layoutGrid');
        const stored = JSON.parse(document.getElementById('layout_json').value || '{"slots":[]}');

        function drawSlots() {
            canvas.innerHTML = '';

            const slots = stored.slots || [
                { x: 0, y: 0, w: 1, h: 1, label: 'Main Character' },
                { x: 1, y: 0, w: 1, h: 1, label: 'Ride or Die' },
                { x: 0, y: 1, w: 1, h: 1, label: 'Trendsetter' },
                { x: 1, y: 1, w: 1, h: 1, label: 'Chaos' }
            ];

            slots.forEach((slot, index) => {
                const block = document.createElement('div');
                block.className = 'slot-box';
                block.style.gridColumn = `${slot.x + 1} / span ${slot.w || 1}`;
                block.style.gridRow = `${slot.y + 1} / span ${slot.h || 1}`;
                block.innerHTML = `<span>${slot.label || 'Slot ' + (index + 1)}</span>`;
                canvas.appendChild(block);
            });
        }

        drawSlots();
    </script>
@endsection
