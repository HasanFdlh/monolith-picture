<?php

namespace App\Http\Controllers;

use App\Models\PhotoFrame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoFrameController extends Controller
{
    public function index()
    {
        $frames = PhotoFrame::latest()->get();
        $total = $frames->count();

        return view('frames.index', compact('frames', 'total'));
    }

    public function create()
    {
        return view('frames.create');
    }

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
            'file' => ['required', 'file', 'mimes:png,jpg,jpeg,webp'],
        ]);

        $file = $request->file('file');
        $path = $file->store('frames', 'public');

        $frame = PhotoFrame::create([
            'name' => $validated['name'],
            'category' => $validated['category'] ?? 'All',
            'scope' => $validated['scope'] ?? 'Generic',
            'status' => $validated['status'] ?? 'active',
            'print_size' => $validated['print_size'] ?? 'Normal',
            'printer_setting' => $validated['printer_setting'] ?? 'Primary Printer',
            'file_path' => $path,
            'thumbnail_path' => $path,
            'is_active' => ($validated['status'] ?? 'active') === 'active',
            'layout_json' => $validated['layout_json'] ?? json_encode([
                'slots' => [],
                'width' => 1000,
                'height' => 700,
            ]),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('frames.index')->with('success', 'Photo frame uploaded successfully.');
    }

    public function edit(PhotoFrame $frame)
    {
        return view('frames.edit', compact('frame'));
    }

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
            'file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp'],
        ]);

        if ($request->hasFile('file')) {
            if ($frame->file_path && Storage::disk('public')->exists($frame->file_path)) {
                Storage::disk('public')->delete($frame->file_path);
            }

            $validated['file_path'] = $request->file('file')->store('frames', 'public');
            $validated['thumbnail_path'] = $validated['file_path'];
        }

        $validated['is_active'] = ($validated['status'] ?? $frame->status) === 'active';
        $validated['status'] = $validated['status'] ?? $frame->status;

        $frame->update($validated);

        return redirect()->route('frames.index')->with('success', 'Photo frame updated successfully.');
    }

    public function destroy(PhotoFrame $frame)
    {
        if ($frame->file_path && Storage::disk('public')->exists($frame->file_path)) {
            Storage::disk('public')->delete($frame->file_path);
        }

        $frame->delete();

        return redirect()->route('frames.index')->with('success', 'Photo frame deleted successfully.');
    }
}
