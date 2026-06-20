@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <form method="GET" class="card shadow-sm border-0 rounded-4 p-3 mt-3">

        <div class="row g-2 align-items-end">

            {{-- SEARCH --}}
            <div class="col-md-3">
                <label class="form-label">Search Session</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                    placeholder="Session code / booth">
            </div>

            {{-- BOOTH FILTER --}}
            <div class="col-md-2">
                <label class="form-label">Booth</label>
                <select name="booth_id" class="form-select">
                    <option value="">All Booth</option>
                    @foreach ($booths as $booth)
                        <option value="{{ $booth->id }}" {{ request('booth_id') == $booth->id ? 'selected' : '' }}>
                            {{ $booth->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- DATE FROM --}}
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control">
            </div>

            {{-- DATE TO --}}
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control">
            </div>

            {{-- LIMIT --}}
            <div class="col-md-1">
                <label class="form-label">Limit</label>
                <select name="limit" class="form-select">
                    <option value="10" {{ request('limit', 10) == 10 ? 'selected' : '' }}>10</option>
                    <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>

            {{-- BUTTON --}}
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100">
                    Filter
                </button>

                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary w-100">
                    Reset
                </a>
            </div>

        </div>

    </form>

    <div class="row mt-4">

        @forelse($sessions as $session)
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">

                <div class="card shadow-sm border-0 rounded-4 session-card h-100">

                    {{-- PREVIEW IMAGE --}}
                    <img src="{{ optional($session->media->first())->path ?? 'https://placehold.co/600x400' }}"
                        class="card-img-top session-img" />

                    <div class="card-body d-flex flex-column">

                        {{-- BOOTH NAME --}}
                        <h5 class="mb-1">
                            {{ $session->booth->name ?? 'No Booth' }}
                        </h5>

                        {{-- SESSION DATE --}}
                        <small class="text-muted d-block">
                            {{ optional($session->created_at)->format('d M Y H:i') }}
                        </small>

                        {{-- SESSION CODE --}}
                        <small class="text-muted d-block">
                            {{ $session->session_code ?? '-' }}
                        </small>

                        <hr>

                        {{-- STATS --}}
                        <div class="d-flex justify-content-between">

                            <div>
                                <small class="text-muted">Photos</small>
                                <div>
                                    <strong>{{ $session->total_files ?? 0 }}</strong>
                                </div>
                            </div>

                            <div>
                                <small class="text-muted">Size</small>
                                <div>
                                    <strong>
                                        {{ number_format(($session->total_size ?? 0) / 1024, 2) }} KB
                                    </strong>
                                </div>
                            </div>

                        </div>

                        {{-- ACTION --}}
                        <div class="mt-auto d-flex gap-2">

                            <a href="{{ route('sessions.show', $session->id) }}" class="btn btn-primary btn-sm w-50">
                                Detail
                            </a>

                            <a href="{{ route('sessions.download', $session->id) }}" class="btn btn-success btn-sm w-50">
                                Download ZIP
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        @empty

            <div class="col-12 text-center text-muted">
                No sessions found
            </div>
        @endforelse

    </div>

    @if ($sessions->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-4">

            <div class="text-muted">
                Showing {{ $sessions->firstItem() }} -
                {{ $sessions->lastItem() }}
                of {{ $sessions->total() }} sessions
            </div>

            {{ $sessions->links() }}

        </div>
    @endif

@endsection
