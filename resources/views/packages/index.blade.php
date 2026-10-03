@extends('layouts.app')

@section('title', 'Paket Foto')

@section('content')
    @if (session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger mt-3">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 mt-3">
        <div class="card-body p-4">
            <h5 class="mb-3">Tambah Paket</h5>
            <form action="{{ route('packages.store') }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small">Nama</label>
                    <input name="name" class="form-control" required placeholder="Classic (4 foto)">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Jumlah take</label>
                    <input type="number" name="takes" class="form-control" min="1" required value="4">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Harga (Rp)</label>
                    <input type="number" name="price" class="form-control" min="0" step="500" required value="35000">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Deskripsi</label>
                    <input name="description" class="form-control" placeholder="Opsional">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Urutan</label>
                    <input type="number" name="sort_order" class="form-control" min="0" value="0">
                </div>
                <div class="col-md-1">
                    <input type="hidden" name="is_active" value="1">
                    <button class="btn btn-primary w-100">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mt-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Take</th>
                            <th>Harga</th>
                            <th>Deskripsi</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($packages as $package)
                            <tr>
                                <td>
                                    <input form="pkg-update-{{ $package->id }}" name="name"
                                        class="form-control form-control-sm" value="{{ $package->name }}" required>
                                </td>
                                <td style="width:90px">
                                    <input form="pkg-update-{{ $package->id }}" type="number" name="takes"
                                        class="form-control form-control-sm" value="{{ $package->takes }}" min="1"
                                        required>
                                </td>
                                <td style="width:150px">
                                    <input form="pkg-update-{{ $package->id }}" type="number" name="price"
                                        class="form-control form-control-sm" value="{{ (float) $package->price }}"
                                        min="0" step="500" required>
                                </td>
                                <td>
                                    <input form="pkg-update-{{ $package->id }}" name="description"
                                        class="form-control form-control-sm" value="{{ $package->description }}">
                                </td>
                                <td style="width:110px">
                                    <span
                                        class="badge {{ $package->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $package->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end" style="width:230px">
                                    <form id="pkg-update-{{ $package->id }}"
                                        action="{{ route('packages.update', $package) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="is_active"
                                            value="{{ $package->is_active ? 1 : 0 }}">
                                        <input type="hidden" name="sort_order" value="{{ $package->sort_order }}">
                                        <button class="btn btn-sm btn-outline-primary">Simpan</button>
                                    </form>
                                    <form action="{{ route('packages.toggle-status', $package) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            class="btn btn-sm btn-outline-secondary">{{ $package->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    <form action="{{ route('packages.destroy', $package) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Hapus paket ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada paket.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
