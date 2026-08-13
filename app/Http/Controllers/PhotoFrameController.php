<?php

namespace App\Http\Controllers;

use App\Models\PhotoFrame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotoFrameController extends Controller
{
    public function index()
    {
        $frames = PhotoFrame::latest()->get();
        $total = $frames->count();

        return view('frames.index', [
            'title' => 'Photo Frames Management',
            'subtitle' => 'Upload, edit, and manage your photo frame templates',
            'frames' => $frames,
            'total' => $total,
        ]);
    }

    public function create()
    {
        return view('frames.create');
    }

    /**
     * Upload new frame
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
            'print_size' => ['nullable', 'string', 'max:255'],
            'printer_setting' => ['nullable', 'string', 'max:255'],
            'layout_json' => ['nullable', 'json'],
            'notes' => ['nullable', 'string'],

            'file' => [
                'required',
                'file',
                'mimes:png,jpg,jpeg,webp',
                'max:10240', // 10 MB
            ],
        ]);

        $file = $request->file('file');

        // Simpan file
        $path = $file->store('frames', 'public');

        $status = $validated['status'] ?? 'active';

        PhotoFrame::create([
            'name' => $validated['name'],
            'category' => $validated['category'] ?? 'All',
            'scope' => $validated['scope'] ?? 'Generic',
            'status' => $status,

            'print_size' => $validated['print_size'] ?? 'Normal',
            'printer_setting' => $validated['printer_setting'] ?? 'Primary Printer',

            'file_path' => $path,
            'thumbnail_path' => $path,

            'is_active' => $status === 'active',

            'layout_json' => $validated['layout_json'] ?? json_encode([
                'slots' => [],
                'width' => 1000,
                'height' => 700,
            ]),

            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('frames.index')
            ->with('success', 'Photo frame uploaded successfully.');
    }

    public function edit(PhotoFrame $frame)
    {
        return view('frames.edit', compact('frame'));
    }

    /**
     * Update frame
     */
    public function update(Request $request, PhotoFrame $frame)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
            'print_size' => ['nullable', 'string', 'max:255'],
            'printer_setting' => ['nullable', 'string', 'max:255'],
            'layout_json' => ['nullable', 'json'],
            'notes' => ['nullable', 'string'],

            'file' => [
                'nullable',
                'file',
                'mimes:png,jpg,jpeg,webp',
                'max:10240',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update image
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('file')) {

            // Hapus image lama
            if (
                !empty($frame->file_path) &&
                Storage::disk('public')->exists($frame->file_path)
            ) {
                Storage::disk('public')->delete($frame->file_path);
            }

            // Upload image baru
            $newPath = $request
                ->file('file')
                ->store('frames', 'public');

            $validated['file_path'] = $newPath;
            $validated['thumbnail_path'] = $newPath;
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $status = $validated['status'] ?? $frame->status ?? 'active';

        $validated['status'] = $status;
        $validated['is_active'] = $status === 'active';

        $frame->update($validated);

        return redirect()
            ->route('frames.index')
            ->with('success', 'Photo frame updated successfully.');
    }

    /**
     * Toggle Active / Inactive
     */
    public function toggleStatus(Request $request, PhotoFrame $frame)
    {
        $frame->is_active = !$frame->is_active;

        $frame->status = $frame->is_active
            ? 'active'
            : 'inactive';

        $frame->save();

        return response()->json([
            'success' => true,
            'message' => 'Frame status updated successfully.',
            'is_active' => $frame->is_active,
            'status' => $frame->status,
        ]);
    }

    /**
     * Delete frame
     */
    public function destroy(PhotoFrame $frame)
    {
        // Delete image
        if (
            !empty($frame->file_path) &&
            Storage::disk('public')->exists($frame->file_path)
        ) {
            Storage::disk('public')->delete($frame->file_path);
        }

        // Delete thumbnail jika berbeda dari file utama
        if (
            !empty($frame->thumbnail_path) &&
            $frame->thumbnail_path !== $frame->file_path &&
            Storage::disk('public')->exists($frame->thumbnail_path)
        ) {
            Storage::disk('public')->delete($frame->thumbnail_path);
        }

        $frame->delete();

        return redirect()
            ->route('frames.index')
            ->with('success', 'Photo frame deleted successfully.');
    }
}
