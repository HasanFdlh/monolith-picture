<?php

namespace App\Http\Controllers;

use App\Models\PhotoPackage;
use Illuminate\Http\Request;

class PhotoPackageController extends Controller
{
    public function index()
    {
        return view('packages.index', [
            'title' => 'Paket Foto',
            'subtitle' => 'Atur jumlah take foto dan harganya',
            'packages' => PhotoPackage::orderBy('sort_order')->orderBy('takes')->get(),
        ]);
    }

    public function store(Request $request)
    {
        PhotoPackage::create($this->validatePackage($request));

        return redirect()->route('packages.index')->with('success', 'Paket berhasil ditambahkan.');
    }

    public function update(Request $request, PhotoPackage $package)
    {
        $package->update($this->validatePackage($request));

        return redirect()->route('packages.index')->with('success', 'Paket berhasil diperbarui.');
    }

    public function destroy(PhotoPackage $package)
    {
        $package->delete();

        return redirect()->route('packages.index')->with('success', 'Paket berhasil dihapus.');
    }

    public function toggleStatus(PhotoPackage $package)
    {
        $package->update(['is_active' => !$package->is_active]);

        return redirect()
            ->route('packages.index')
            ->with('success', $package->is_active ? 'Paket diaktifkan.' : 'Paket dinonaktifkan.');
    }

    private function validatePackage(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'takes' => ['required', 'integer', 'min:1', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
