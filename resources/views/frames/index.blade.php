@extends('layouts.app')

@section('title', 'Photo Frames Management')

@section('content')
  <div class="frame-shell">

    @if (session('success'))
      <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    <div class="upload-panel">
      <h2>Upload New Photo Frame</h2>
      <p>
        Add a PNG template with a compact dropzone.
        Open advanced metadata only when you need custom defaults.
      </p>

      <form action="{{ route('frames.store') }}" method="POST" enctype="multipart/form-data" class="frame-upload-form">
        @csrf

        {{-- Frame Name --}}
        <div class="upload-name-field">
          <label for="frame-name">
            Frame Name
          </label>

          <input type="text" id="frame-name" name="name" value="{{ old('name') }}"
            placeholder="Example: Wedding Frame 01" required>

          @error('name')
            <small class="text-danger">
              {{ $message }}
            </small>
          @enderror
        </div>


        {{-- Upload Dropzone --}}
        <div class="upload-dropzone" id="uploadDropzone">

          <div class="upload-icon">
            <i class="fa-solid fa-cloud-arrow-up"></i>
          </div>


          <div class="upload-copy">

            <div class="upload-title" id="uploadTitle">
              Drop an image here or click to choose
            </div>

            <div class="upload-subtitle" id="uploadSubtitle">
              PNG, JPG, JPEG or WEBP.
              Portrait and landscape frames are supported.
            </div>


            {{-- Selected File --}}
            <div class="selected-file" id="selectedFile">

              <i class="fa-regular fa-file-image"></i>

              <div class="selected-file-info">

                <span class="selected-file-name" id="selectedFileName"></span>

                <span class="selected-file-size" id="selectedFileSize"></span>

              </div>

              <button type="button" class="remove-file" id="removeFile">
                <i class="fa-solid fa-xmark"></i>
              </button>

            </div>

          </div>


          <label class="browse-btn">

            <span>
              Browse Image
            </span>

            <input type="file" id="frameFile" name="file" accept=".png,.jpg,.jpeg,.webp" required>

          </label>

        </div>


        @error('file')
          <small class="text-danger">
            {{ $message }}
          </small>
        @enderror


        {{-- =====================================================
        ADVANCED METADATA
    ====================================================== --}}

        <div class="advanced-metadata" id="advancedMetadata">

          <button type="button" class="advanced-header" id="advancedToggle">

            <div class="advanced-title">
              <i class="fa-solid fa-sliders"></i>

              <span>
                Advanced metadata
              </span>
            </div>

            <i class="fa-solid fa-chevron-up advanced-chevron" id="advancedChevron"></i>

          </button>


          <div class="advanced-content" id="advancedContent">

            {{-- Row 1 --}}
            <div class="metadata-grid metadata-grid-two">

              {{-- Category --}}
              <div class="metadata-field">

                <label for="category">
                  Category
                </label>

                <select name="category" id="category">
                  <option value="All">
                    All
                  </option>

                  @foreach ($frames->pluck('category')->filter()->unique()->sort() as $category)
                    @if ($category !== 'All')
                      <option value="{{ $category }}" {{ old('category') === $category ? 'selected' : '' }}>
                        {{ $category }}
                      </option>
                    @endif
                  @endforeach

                </select>

                <button type="button" class="create-category">
                  <i class="fa-solid fa-plus"></i>
                  Create New
                </button>

              </div>


              {{-- Status --}}
              <div class="metadata-field">

                <label for="status">
                  Status
                </label>

                <select name="status" id="status">
                  <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>
                    Active
                  </option>

                  <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>
                    Inactive
                  </option>
                </select>

              </div>

            </div>


            {{-- Row 2 --}}
            <div class="metadata-grid metadata-grid-three">

              {{-- Scope --}}
              <div class="metadata-field">

                <label for="scope">
                  Frame Scope
                </label>

                <select name="scope" id="scope">
                  <option value="Generic">
                    Generic
                  </option>

                  @foreach ($frames->pluck('scope')->filter()->unique()->sort() as $scope)
                    @if ($scope !== 'Generic')
                      <option value="{{ $scope }}" {{ old('scope') === $scope ? 'selected' : '' }}>
                        {{ $scope }}
                      </option>
                    @endif
                  @endforeach

                </select>

              </div>


              {{-- Print Size --}}
              <div class="metadata-field">

                <label for="print_size">
                  Print Size
                </label>

                <select name="print_size" id="print_size">
                  <option value="Normal">
                    Normal
                  </option>

                  <option value="4R">
                    4R
                  </option>

                  <option value="5R">
                    5R
                  </option>

                  <option value="6R">
                    6R
                  </option>

                  <option value="A4">
                    A4
                  </option>

                </select>

              </div>


              {{-- Printer --}}
              <div class="metadata-field">

                <label for="printer_setting">
                  Printer Setting
                </label>

                <select name="printer_setting" id="printer_setting">
                  <option value="Primary Printer">
                    Primary Printer
                  </option>

                  <option value="Secondary Printer">
                    Secondary Printer
                  </option>
                </select>

              </div>

            </div>

          </div>

        </div>


        {{-- Submit --}}
        <div class="upload-submit-wrap">

          <button type="submit" class="upload-submit-btn" id="uploadSubmitBtn">
            <i class="fa-solid fa-cloud-arrow-up"></i>

            Upload Frame
          </button>

        </div>

      </form>
    </div>

    <div class="filter-panel">

      {{-- Filter Header --}}
      <div class="filter-header">
        <div>
          <h3>Frame Library</h3>
          <p>Search and filter your photo frames</p>
        </div>
      </div>


      {{-- Filter Controls --}}
      <div class="filter-controls">

        {{-- Search --}}
        <div class="filter-item filter-search">
          <label for="frameSearch">
            Search
          </label>

          <div class="input-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>

            <input type="text" id="frameSearch" placeholder="Search frame name, scope or category..."
              autocomplete="off">

            <button type="button" id="clearSearch" class="clear-search" aria-label="Clear search">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </div>


        {{-- Scope --}}
        <div class="filter-item">
          <label for="scopeFilter">
            Scope
          </label>

          <select id="scopeFilter">
            <option value="">All</option>

            @foreach ($frames->pluck('scope')->filter()->unique()->sort() as $scope)
              <option value="{{ strtolower($scope) }}">
                {{ $scope }}
              </option>
            @endforeach
          </select>
        </div>


        {{-- Category --}}
        <div class="filter-item">
          <label for="categoryFilter">
            Category
          </label>

          <select id="categoryFilter">
            <option value="">All Categories</option>

            @foreach ($frames->pluck('category')->filter()->unique()->sort() as $category)
              <option value="{{ strtolower($category) }}">
                {{ $category }}
              </option>
            @endforeach
          </select>
        </div>


        {{-- Sort --}}
        <div class="filter-item">
          <label for="sortFilter">
            Sort
          </label>

          <select id="sortFilter">
            <option value="newest">
              Date (Newest First)
            </option>

            <option value="oldest">
              Date (Oldest First)
            </option>

            <option value="name_asc">
              Name (A-Z)
            </option>

            <option value="name_desc">
              Name (Z-A)
            </option>
          </select>
        </div>

      </div>


      {{-- Filter Bottom --}}
      <div class="filter-footer">

        {{-- Result --}}
        <div class="results-tag" id="resultsTag">

          <span class="results-icon">
            <i class="fa-regular fa-image"></i>
          </span>

          <div class="results-content">
            <strong id="resultsText">
              {{ $frames->count() }} matching frames
            </strong>

            <span>
              of {{ $total }} total frames
            </span>
          </div>

        </div>


        {{-- Right Controls --}}
        <div class="filter-footer-actions">

          {{-- Per Page --}}
          <div class="per-page">
            <label for="perPage">
              Frames per page
            </label>

            <select id="perPage">
              <option value="12">12</option>
              <option value="24">24</option>
              <option value="48">48</option>
              <option value="all">All</option>
            </select>
          </div>



        </div>

      </div>

    </div>

    <div class="frame-grid" id="frameGrid">

      @forelse($frames as $frame)
        <div class="frame-card"
          data-search="{{ strtolower(
              ($frame->name ?? '') . ' ' . ($frame->scope ?? '') . ' ' . ($frame->category ?? '') . ' ' . ($frame->status ?? ''),
          ) }}">

          <div class="frame-top">
            <h3>{{ $frame->name }}</h3>

            <div class="toggle-wrap">
              <label class="switch">
                <input type="checkbox" class="frame-status-toggle"
                  data-url="{{ route('frames.toggle-status', $frame) }}" {{ $frame->is_active ? 'checked' : '' }}>

                <span class="slider"></span>
              </label>

              <span class="toggle-text">
                {{ $frame->is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>

          <div class="frame-meta">
            @php
              $fileSize = 0;

              if (!empty($frame->file_path) && file_exists(storage_path('app/public/' . $frame->file_path))) {
                  $fileSize = filesize(storage_path('app/public/' . $frame->file_path));
              }
            @endphp

            <div>
              {{ number_format($fileSize / 1024, 2) }} KB
            </div>

            <div>
              Created {{ optional($frame->created_at)->format('d/m/Y') }}
            </div>
          </div>

          <div class="frame-preview">
            @if (!empty($frame->file_path) && file_exists(storage_path('app/public/' . $frame->file_path)))
              <img src="{{ asset('storage/' . $frame->file_path) }}" alt="{{ $frame->name }}">
            @else
              <div class="empty-preview">
                No preview
              </div>
            @endif
          </div>

          <div class="frame-badges">
            <span class="badge badge-all">
              {{ $frame->category ?? 'All' }}
            </span>

            <span class="badge badge-scope">
              {{ $frame->scope ?? 'Generic' }}
            </span>

            <span class="badge badge-status">
              {{ ucfirst($frame->status ?? 'Active') }}
            </span>
          </div>

          <div class="frame-actions">
            <a href="{{ route('frames.edit', $frame) }}" class="btn-edit">
              Edit Layout
            </a>

            <form action="{{ route('frames.destroy', $frame) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')

              <button type="submit" class="btn-delete">
                Delete
              </button>
            </form>
          </div>

        </div>

      @empty

        <div class="empty-state">
          No photo frames uploaded yet.
        </div>
      @endforelse

    </div>
  </div>
@endsection
