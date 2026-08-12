@extends('layouts.app')

@section('title', 'Photo Frames Management')

@section('content')
    <div class="frame-shell">
        <div class="frame-header">
            <div class="frame-header-icon">
                <i class="fa-regular fa-image"></i>
            </div>
            <div>
                <h1>Photo Frames Management</h1>
                <p>Upload, edit, and manage your photo frame templates</p>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif

        <div class="upload-panel">
            <h2>Upload New Photo Frame</h2>
            <p>Add a PNG template with a compact dropzone. Open advanced metadata only when you need custom defaults.</p>

            <form action="{{ route('frames.store') }}" method="POST" enctype="multipart/form-data" class="frame-upload-form">
                @csrf

                <div class="upload-dropzone">
                    <div class="upload-icon">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <div class="upload-copy">
                        <div class="upload-title">Drop a PNG here or click to choose</div>
                        <div class="upload-subtitle">PNG template only. Portrait and landscape frames are supported.</div>
                    </div>
                    <label class="browse-btn">Browse PNG
                        <input type="file" name="file" accept=".png,.jpg,.jpeg,.webp" required>
                    </label>
                </div>

                <div class="upload-submit-wrap">
                    <button type="submit" class="upload-submit-btn">Upload Frame</button>
                </div>
            </form>
        </div>

        <div class="filter-panel">
            <div class="toolbar-row">
                <div class="toolbar-field search-field">
                    <span class="field-label-inline">Search</span>
                    <div class="input-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search frame name or booth name" />
                    </div>
                </div>

                <div class="toolbar-field">
                    <label>Scope</label>
                    <select>
                        <option>All</option>
                    </select>
                </div>

                <div class="toolbar-field">
                    <label>Category</label>
                    <select>
                        <option>All Categories</option>
                    </select>
                </div>

                <div class="toolbar-field">
                    <label>Sort</label>
                    <select>
                        <option>Date (Newest First)</option>
                    </select>
                </div>

                <div class="toolbar-field small-field">
                    <label>Frames per page</label>
                    <select>
                        <option>12</option>
                    </select>
                </div>

                <div class="toolbar-actions">
                    <button type="button" class="btn-manage">Manage Categories</button>
                    <button type="button" class="btn-select">Select</button>
                </div>
            </div>

            <div class="results-tag">
                <i class="fa-regular fa-image"></i>
                {{ $frames->count() }} matching frames of {{ $total }} total
            </div>
        </div>

        <div class="frame-grid">
            @forelse($frames as $frame)
                <div class="frame-card">
                    <div class="frame-top">
                        <h3>{{ $frame->name }}</h3>
                        <div class="toggle-wrap">
                            <label class="switch">
                                <input type="checkbox" {{ $frame->is_active ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                            <span class="toggle-text">{{ $frame->status ?? 'Active' }}</span>
                        </div>
                    </div>

                    <div class="frame-meta">
                        @php
                            $fileSize = 0;
                            if (!empty($frame->file_path) && file_exists(storage_path('app/public/' . $frame->file_path))) {
                                $fileSize = filesize(storage_path('app/public/' . $frame->file_path));
                            }
                        @endphp
                        <div>{{ number_format($fileSize / 1024, 2) }} KB</div>
                        <div>Created {{ optional($frame->created_at)->format('d/m/Y') }}</div>
                    </div>

                    <div class="frame-preview">
                        @if(!empty($frame->file_path) && file_exists(storage_path('app/public/' . $frame->file_path)))
                            <img src="{{ asset('storage/' . $frame->file_path) }}" alt="{{ $frame->name }}">
                        @else
                            <div class="empty-preview">No preview</div>
                        @endif
                    </div>

                    <div class="frame-badges">
                        <span class="badge badge-all">All</span>
                        <span class="badge badge-scope">{{ $frame->scope ?? 'Generic' }}</span>
                        <span class="badge badge-status">{{ ucfirst($frame->status ?? 'Active') }}</span>
                    </div>

                    <div class="frame-actions">
                        <a href="{{ route('frames.edit', $frame) }}" class="btn-edit">Edit Layout</a>
                        <form action="{{ route('frames.destroy', $frame) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="empty-state">No photo frames uploaded yet.</div>
            @endforelse
        </div>
    </div>
@endsection
