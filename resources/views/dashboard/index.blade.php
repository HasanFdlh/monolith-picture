@extends('layouts.app')

@section('title','Dashboard')

@section('content')

<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body">

        <div class="row g-3">

            <div class="col-md-4">
                <label class="form-label">Booth</label>
                <select class="form-select">
                    <option value="">All Booths</option>
                    @foreach($booths as $booth)
                        <option value="{{ $booth->id }}">
                            {{ $booth->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Date</label>
                <input type="date" class="form-control">
            </div>

        </div>

    </div>
</div>

{{-- STAT CARD --}}
<div class="row mt-4">

    <div class="col-md-3">
        <div class="card shadow-sm border-0 rounded-4 p-3">
            <h6>Total Booth</h6>
            <h3>{{ $totalBooth }}</h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 rounded-4 p-3">
            <h6>Total Session</h6>
            <h3>{{ $totalSession }}</h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 rounded-4 p-3">
            <h6>Total Media</h6>
            <h3>{{ $totalMedia }}</h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 rounded-4 p-3">
            <h6>Total Share</h6>
            <h3>{{ $totalShare }}</h3>
        </div>
    </div>

</div>

{{-- SESSION LIST --}}
<div class="row mt-4">

    @forelse($latestSessions as $session)

    <div class="col-lg-4 col-md-6 mb-3">

        <div class="card session-card shadow-sm border-0">

            <img src="https://placehold.co/600x350" class="card-img-top">

            <div class="card-body">

                <h5>
                    {{ $session->booth->name ?? 'No Booth' }}
                </h5>

                <p class="text-muted mb-1">
                    Session: {{ $session->id }}
                </p>

                <small class="text-muted">
                    {{ $session->created_at->format('d M Y') }}
                </small>

                <div class="mt-3 d-flex gap-2">

                    <button class="btn btn-success btn-sm">Download</button>
                    <button class="btn btn-primary btn-sm">Share</button>
                    <button class="btn btn-danger btn-sm">Delete</button>

                </div>

            </div>

        </div>

    </div>

    @empty

    <div class="col-12 text-center text-muted">
        No session data
    </div>

    @endforelse

</div>

@endsection
