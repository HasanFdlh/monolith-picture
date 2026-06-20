@extends('layouts.app')

@section('title','Dashboard')

@section('content')

<div class="card shadow-sm border-0 rounded-4">

    <div class="card-body">

        <div class="row">

            <div class="col-md-4">

                <label class="form-label">
                    Booth
                </label>

                <select class="form-select">

                    <option>All Booths</option>

                </select>

            </div>

            <div class="col-md-4">

                <label class="form-label">

                    Date

                </label>

                <input
                    type="date"
                    class="form-control">

            </div>

        </div>

    </div>

</div>

<div class="row mt-4">

    @for($i=0;$i<6;$i++)

    <div class="col-lg-4">

        <div class="card session-card">

            <img src="https://placehold.co/600x350"
                class="card-img-top">

            <div class="card-body">

                <h5>2000s Booth</h5>

                <p class="text-muted">

                    Session :
                    20260322181515

                </p>

                <small>

                    9 Files

                    •

                    8.74 MB

                </small>

                <br>

                <small>

                    20 June 2026

                </small>

                <div class="mt-3 d-flex gap-2">

                    <button class="btn btn-success btn-sm">

                        Download

                    </button>

                    <button class="btn btn-primary btn-sm">

                        Share

                    </button>

                    <button class="btn btn-danger btn-sm">

                        Delete

                    </button>

                </div>

            </div>

        </div>

    </div>

    @endfor

</div>

@endsection
