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
        $layout = $this->validatedLayout($validated['layout_json'] ?? null);

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

            'layout_json' => $layout ?? [
                'slots' => [],
                'width' => 1000,
                'height' => 700,
            ],

            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('frames.index')
            ->with('success', 'Photo frame uploaded successfully.');
    }

    public function edit(PhotoFrame $frame)
    {
        return view('frames.edit', [
            'title' => 'Edit Photo Frames Management',
            'subtitle' => 'Edit your photo frame templates',
            'frame' => $frame,
        ]);
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
        $validated['layout_json'] = $this->validatedLayout($validated['layout_json'] ?? null) ?? $frame->layout_json;

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

    /**
     * Layout coordinates use percentages so they can be rendered consistently
     * for previews and any print resolution.
     */
    private function validatedLayout(?string $layoutJson): ?array
    {
        if (!$layoutJson) {
            return null;
        }

        $layout = json_decode($layoutJson, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($layout) || !is_array($layout['slots'] ?? null) || count($layout['slots']) > 12) {
            abort(422, 'Invalid photo layout.');
        }

        $slots = collect($layout['slots'])->map(function ($slot, $index) {
            if (!is_array($slot)) {
                abort(422, 'Invalid photo slot.');
            }

            $x = (float) ($slot['x'] ?? 0);
            $y = (float) ($slot['y'] ?? 0);
            $w = (float) ($slot['w'] ?? 0);
            $h = (float) ($slot['h'] ?? 0);

            if ($x < 0 || $y < 0 || $w < 1 || $h < 1 || $x + $w > 100 || $y + $h > 100) {
                abort(422, 'Photo slot must stay within the frame.');
            }

            return [
                'id' => substr((string) ($slot['id'] ?? Str::uuid()), 0, 64),
                'label' => Str::limit(trim((string) ($slot['label'] ?? 'Foto ' . ($index + 1))), 50, ''),
                'x' => round($x, 3),
                'y' => round($y, 3),
                'w' => round($w, 3),
                'h' => round($h, 3),
            ];
        })->all();

        return [
            'width' => max(1, (int) ($layout['width'] ?? 1000)),
            'height' => max(1, (int) ($layout['height'] ?? 700)),
            'slots' => $slots,
        ];
    }
}
