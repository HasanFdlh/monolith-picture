<?php

namespace App\Http\Controllers;

use App\Models\PhotoFrame;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CaptureController extends Controller
{
    public function show(Session $session)
    {
        $session->load('photoPackage', 'media');

        // Gate utama: hanya sesi yang sudah dibayar yang bisa dipakai.
        if (!$session->isPaid()) {
            return redirect()
                ->route('checkout.index')
                ->with('error', 'Sesi ini belum dibayar. Silakan pilih paket dan selesaikan pembayaran.');
        }

        return view('capture.show', [
            'title' => 'Sesi Foto',
            'subtitle' => 'Ambil foto sesuai jumlah take yang sudah dibayar.',
            'session' => $session,
            'remainingTakes' => $session->remainingTakes(),
        ]);
    }

    public function store(Request $request, Session $session)
    {
        abort_unless($session->isPaid(), 403, 'Sesi belum dibayar.');

        $request->validate([
            'photo_frame_id' => ['required', 'integer', 'exists:photo_frames,id'],
            'photos' => ['required', 'array', 'min:1', 'max:24'],
            'photos.*' => ['file', 'image', 'max:10240'],
        ]);

        $frame = PhotoFrame::findOrFail($request->input('photo_frame_id'));
        $photos = $request->file('photos', []);
        $remaining = $session->remainingTakes();

        // Batasi sesuai jumlah take yang dibayar.
        abort_if(count($photos) > $remaining, 422, "Sisa take Anda hanya {$remaining} foto.");

        $slotCount = count($frame->layout_json['slots'] ?? []);
        abort_if(
            $slotCount > 0 && count($photos) !== $slotCount,
            422,
            "Template {$frame->name} membutuhkan {$slotCount} foto."
        );

        foreach ($photos as $photo) {
            $fileName = Str::uuid() . '.' . $photo->extension();
            $path = $photo->storeAs("sessions/{$session->session_code}", $fileName, 'public');

            $session->media()->create([
                'type' => 'photo',
                'file_name' => $fileName,
                'path' => 'storage/' . $path,
                'size' => $photo->getSize(),
            ]);
        }

        $session->update([
            'photo_frame_id' => $frame->id,
            'layout' => $session->layout ?: 'portrait',
            'grid' => $this->gridFromFrame($frame),
            'total_files' => $session->media()->count(),
            'total_size' => $session->media()->sum('size'),
        ]);

        $session->refresh();

        return response()->json([
            'message' => 'Foto berhasil disimpan.',
            'remaining_takes' => $session->remainingTakes(),
            'total_files' => $session->total_files,
            'data' => $session->load('media'),
        ]);
    }

    private function gridFromFrame(PhotoFrame $frame): string
    {
        $slots = $frame->layout_json['slots'] ?? [];

        if (count($slots) === 0) {
            return 'custom';
        }

        $rows = collect($slots)->pluck('y')->map(fn ($value) => (string) $value)->unique()->count();
        $columns = collect($slots)->pluck('x')->map(fn ($value) => (string) $value)->unique()->count();

        return "{$columns}x{$rows}";
    }
}
