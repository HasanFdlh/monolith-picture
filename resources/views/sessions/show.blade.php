@extends('layouts.app')

@section('title','Session Detail')

@section('content')

<div class="container mt-4">

    {{-- HEADER --}}
    <div class="mb-4">

        <h3>{{ $session->booth->name ?? 'No Booth' }}</h3>

        <p class="text-muted mb-1">
            Session Code: {{ $session->session_code }}
        </p>

        <p class="text-muted">
            {{ $session->created_at->format('d M Y H:i') }}
        </p>

        <div class="d-flex gap-4">

            <span>📸 {{ $session->media->count() }} Photos</span>

            <span>📦 {{ number_format($session->media->sum('size') / 1024, 2) }} KB</span>

        </div>

        <div class="mt-3">
            <a href="{{ route('sessions.download', $session->id) }}"
               class="btn btn-success">
                Download ZIP
            </a>
        </div>

    </div>

    {{-- GALLERY GRID --}}
    <div class="row g-3">

        @foreach($session->media as $media)

        <div class="col-lg-3 col-md-4 col-sm-6">

            <div class="gallery-item">

                <img src="{{ $media->path }}" class="img-fluid rounded shadow-sm">

            </div>

        </div>

        @endforeach

    </div>

</div>

@endsection
