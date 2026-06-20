@extends('layouts.app')

@section('title','Dashboard')

@section('content')

<div class="row mt-4">

@forelse($sessions as $session)

    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">

        <div class="card shadow-sm border-0 rounded-4 session-card h-100">

            {{-- PREVIEW IMAGE --}}
            <img
                src="{{ optional($session->media->first())->path ?? 'https://placehold.co/600x400' }}"
                class="card-img-top session-img"
            />

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

                    <a href="{{ route('sessions.show', $session->id) }}"
                       class="btn btn-primary btn-sm w-50">
                        Detail
                    </a>

                    <a href="{{ route('sessions.download', $session->id) }}"
                       class="btn btn-success btn-sm w-50">
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

@endsection
