<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\PhotoFrame;
use App\Models\Session;
use App\Services\SessionPrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotoboothController extends Controller
{
    public function templates(Request $request)
    {
        $frames = PhotoFrame::query()
            ->where('is_active', true)
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->latest()
            ->get();

        return response()->json(['data' => $frames]);
    }

    public function index()
    {
        return response()->json([
            'data' => Session::with(['booth', 'photoFrame', 'media'])->latest()->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        // Sesi baru harus lahir dari pembayaran (POST /api/payments), lalu fotonya
        // di-upload lewat addPhotos. Jalur ini ditutup bila pembayaran diwajibkan.
        abort_if(
            config('photobooth.require_payment'),
            403,
            'Pembayaran diperlukan. Buat pembayaran lewat POST /api/payments, lalu upload foto ke sesi yang sudah dibayar.'
        );

        $data = $this->validateSession($request, true);

        return DB::transaction(function () use ($request, $data) {
            $frame = PhotoFrame::findOrFail($data['photo_frame_id']);
            $slotCount = count($frame->layout_json['slots'] ?? []);
            abort_if(
                $slotCount > 0 && count($request->file('photos', [])) !== $slotCount,
                422,
                "Template {$frame->name} membutuhkan {$slotCount} foto."
            );
            $session = Session::create([
                ...$data,
                'grid' => $this->gridFromFrame($frame),
                'session_code' => now()->format('YmdHis') . Str::upper(Str::random(4)),
                'taken_at' => $data['taken_at'] ?? now(),
                'total_files' => 0,
                'total_size' => 0,
            ]);

            $this->storePhotos($request, $session);
            $session->refresh();

            return response()->json([
                'message' => 'Photo session created.',
                'data' => $session->load(['booth', 'photoFrame', 'media']),
            ], 201);
        });
    }

    public function show(Session $photoSession)
    {
        return response()->json(['data' => $photoSession->load(['booth', 'photoFrame', 'media'])]);
    }

    public function update(Request $request, Session $photoSession)
    {
        $this->ensurePaid($photoSession);

        $data = $this->validateSession($request, false);
        if (array_key_exists('photo_frame_id', $data) && $data['photo_frame_id']) {
            $data['grid'] = $this->gridFromFrame(PhotoFrame::findOrFail($data['photo_frame_id']));
        }
        $photoSession->update($data);

        return response()->json([
            'message' => 'Photo session updated.',
            'data' => $photoSession->fresh()->load(['booth', 'photoFrame', 'media']),
        ]);
    }

    public function addPhotos(Request $request, Session $photoSession)
    {
        $request->validate(['photos' => ['required', 'array', 'min:1'], 'photos.*' => ['file', 'image', 'max:10240']]);
        $this->ensurePaid($photoSession);

        if (config('photobooth.require_payment')) {
            $remaining = $photoSession->remainingTakes();
            abort_if(
                count($request->file('photos', [])) > $remaining,
                422,
                "Sisa take pada sesi ini hanya {$remaining} foto."
            );
        }

        $this->storePhotos($request, $photoSession);

        return response()->json(['data' => $photoSession->fresh()->load('media')], 201);
    }

    public function deletePhoto(Session $photoSession, Media $media)
    {
        abort_unless($media->session_id === $photoSession->id, 404);
        $this->deleteMedia($media);
        $this->refreshTotals($photoSession);

        return response()->json(['message' => 'Photo deleted.']);
    }

    public function destroy(Session $photoSession)
    {
        DB::transaction(function () use ($photoSession) {
            $photoSession->media->each(fn (Media $media) => $this->deleteMedia($media));
            $photoSession->delete();
        });

        return response()->noContent();
    }

    /**
     * Jalankan printer dan/atau kirim email berisi semua foto + mock strip.
     * - email  : opsional; bila ada, email dikirim.
     * - print  : default true; false = jangan jalankan printer (hanya email).
     * - mark_printed : default true; kiosk mengirim false karena sudah memanggil
     *   /printed sendiri (supaya hitungan cetak tidak dobel).
     */
    public function print(Request $request, Session $photoSession, SessionPrintService $printer)
    {
        $data = $request->validate([
            'email' => ['nullable', 'email'],
            'print' => ['sometimes', 'boolean'],
            'mark_printed' => ['sometimes', 'boolean'],
        ]);

        $email = $data['email'] ?? null;
        $print = (bool) ($data['print'] ?? true);
        abort_if(!$email && !$print, 422, 'Isi email atau aktifkan print.');

        $photoSession->load('media');
        abort_if($photoSession->media->isEmpty(), 422, 'Session has no photos to print.');

        $result = $printer->dispatch($photoSession, $email, $print, (bool) ($data['mark_printed'] ?? true));

        // Tidak ada satu pun yang berhasil: jangan balas 200.
        $status = ($result['email_sent'] || $result['print_dispatched']) ? 200 : 502;

        return response()->json($result, $status);
    }

    /**
     * POST /photo-sessions/{id}/strip
     * Kiosk mengunggah gambar mock strip (hasil akhir: frame + background +
     * foto + filter). Disimpan sebagai media bertipe `strip`, menggantikan
     * mock lama; tidak memakan jatah take.
     */
    public function uploadStrip(Request $request, Session $photoSession)
    {
        $request->validate(['strip' => ['required', 'file', 'image', 'max:20480']]);
        $this->ensurePaid($photoSession);

        $photoSession->media()->where('type', 'strip')->get()->each(fn (Media $m) => $this->deleteMedia($m));

        $file = $request->file('strip');
        $fileName = 'strip-' . Str::uuid() . '.' . $file->extension();
        $path = $file->storeAs("sessions/{$photoSession->session_code}", $fileName, 'public');

        $photoSession->media()->create([
            'type' => 'strip',
            'file_name' => $fileName,
            'path' => 'storage/' . $path,
            'size' => $file->getSize(),
        ]);
        $this->refreshTotals($photoSession);

        return response()->json(['data' => $photoSession->fresh()->load('media')], 201);
    }

    /**
     * Dipanggil kiosk saat pelanggan menekan tombol Cetak. Sesi (beserta
     * fotonya) lalu muncul di menu "Hasil Print" pada dashboard.
     */
    public function markPrinted(Request $request, Session $photoSession)
    {
        $this->ensurePaid($photoSession);
        abort_if(!$photoSession->media()->exists(), 422, 'Session has no photos to print.');

        // Susunan akhir terbaru (frame/background/foto pilihan/filter).
        $data = $request->validate(['composition' => ['sometimes', 'nullable', 'array']]);
        if (!empty($data['composition'])) {
            $photoSession->composition = $data['composition'];
            if (array_key_exists('filter', $data['composition'])) {
                $photoSession->filter = $data['composition']['filter'];
            }
        }

        $photoSession->markPrinted();

        return response()->json([
            'message' => 'Session marked as printed.',
            'data' => $photoSession->fresh()->load(['booth', 'photoFrame', 'media']),
        ]);
    }

    private function ensurePaid(Session $session): void
    {
        abort_if(
            config('photobooth.require_payment') && !$session->isPaid(),
            403,
            'Sesi belum dibayar.'
        );
    }

    private function validateSession(Request $request, bool $creating): array
    {
        return $request->validate([
            'booth_id' => [$creating ? 'required' : 'sometimes', 'integer', 'exists:booths,id'],
            'customer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'photo_frame_id' => [$creating ? 'required' : 'sometimes', 'nullable', 'integer', 'exists:photo_frames,id'],
            'layout' => ['sometimes', 'nullable', 'string', 'max:100'],
            'filter' => ['sometimes', 'nullable', 'string', 'max:100'],
            'composition' => ['sometimes', 'nullable', 'array'],
            'taken_at' => ['sometimes', 'nullable', 'date'],
            'photos' => [$creating ? 'required' : 'sometimes', 'array', 'min:1', 'max:24'],
            'photos.*' => ['file', 'image', 'max:10240'],
        ]);
    }

    private function storePhotos(Request $request, Session $session): void
    {
        foreach ($request->file('photos', []) as $photo) {
            $fileName = Str::uuid() . '.' . $photo->extension();
            $path = $photo->storeAs("sessions/{$session->session_code}", $fileName, 'public');
            $session->media()->create([
                // `media.type` is an enum (photo, strip, gif, boomerang, video),
                // not the file MIME type such as image/jpeg.
                'type' => 'photo',
                'file_name' => $fileName,
                'path' => 'storage/' . $path,
                'size' => $photo->getSize(),
            ]);
        }

        $this->refreshTotals($session);
    }

    private function refreshTotals(Session $session): void
    {
        $session->update([
            // Mock strip tidak dihitung sebagai foto (jatah take).
            'total_files' => $session->media()->where('type', '!=', 'strip')->count(),
            'total_size' => $session->media()->sum('size'),
        ]);
    }

    private function deleteMedia(Media $media): void
    {
        Storage::disk('public')->delete(ltrim(preg_replace('#^/?storage/#', '', $media->path), '/'));
        $media->delete();
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
