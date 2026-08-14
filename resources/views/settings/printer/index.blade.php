@extends('layouts.app')

@section('title', 'Printer Settings')

@section('content')

    <div class="card shadow-sm border-0 rounded-4 p-3 mt-3">

        <h3>Printer Settings</h3>
        <p class="text-muted">Configure the connected printer and printing options.</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('printer.settings.save') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Printer Name</label>
                <input type="text" name="printer_name" class="form-control" value="{{ old('printer_name', optional($setting)->printer_name) }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Printer Command</label>
                <input type="text" name="printer_command" class="form-control" value="{{ old('printer_command', optional($setting)->printer_command) }}">
                <small class="text-muted">Use {file} as placeholder for the file path, e.g. <code>lp -d MyPrinter {file}</code></small>
            </div>

            <div class="mb-3">
                <label class="form-label">Copies</label>
                <input type="number" name="copies" min="1" max="10" class="form-control" value="{{ old('copies', optional($setting)->copies ?? 1) }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Paper Size</label>
                <input type="text" name="paper_size" class="form-control" value="{{ old('paper_size', optional($setting)->paper_size) }}">
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" {{ optional($setting)->is_enabled ? 'checked' : '' }}>
                <label class="form-check-label" for="is_enabled">Enable auto-print</label>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary">Save Settings</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Back</a>
            </div>

        </form>

    </div>

@endsection
