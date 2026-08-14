<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SessionPrintMail;
use App\Models\Media;
use App\Models\PhotoFrame;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

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

    public function print(Request $request, Session $photoSession)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $photoSession->load('media');
        abort_if($photoSession->media->isEmpty(), 422, 'Session has no photos to print.');

        $zipPath = $this->createZip($photoSession);
        Mail::to($data['email'])->send(new SessionPrintMail($photoSession, $zipPath));

        $printerCommand = config('photobooth.printer_command');
        $printed = false;
        if ($printerCommand) {
            $command = str_replace('{file}', escapeshellarg($zipPath), $printerCommand);
            exec($command, $output, $status);
            $printed = $status === 0;
        }

        $photoSession->update(['email' => $data['email']]);

        return response()->json([
            'message' => $printed ? 'Print job sent and email sent.' : 'Email sent. Configure PHOTOBOOTH_PRINTER_COMMAND to dispatch a print job.',
            'email_sent' => true,
            'print_dispatched' => $printed,
        ]);
    }

    private function validateSession(Request $request, bool $creating): array
    {
        return $request->validate([
            'booth_id' => [$creating ? 'required' : 'sometimes', 'integer', 'exists:booths,id'],
            'customer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'photo_frame_id' => [$creating ? 'required' : 'sometimes', 'nullable', 'integer', 'exists:photo_frames,id'],
            'layout' => ['sometimes', 'nullable', 'string', 'max:100'],
            'filter' => ['sometimes', 'nullable', 'string', 'max:100'],
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
            'total_files' => $session->media()->count(),
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

    private function createZip(Session $session): string
    {
        $path = storage_path("app/temp/session-{$session->session_code}.zip");
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);

        $zip = new ZipArchive();
        abort_unless($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Cannot create print package.');
        foreach ($session->media as $media) {
            $storedPath = ltrim(preg_replace('#^/?storage/#', '', $media->path), '/');
            if (Storage::disk('public')->exists($storedPath)) {
                $zip->addFromString($media->file_name, Storage::disk('public')->get($storedPath));
            }
        }
        $zip->close();

        return $path;
    }
}
