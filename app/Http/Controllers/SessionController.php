<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\FrameBackground;
use App\Models\Media;
use App\Models\PhotoFrame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Services\SessionPrintService;

class SessionController extends Controller
{
    public function show($id)
    {
        $session = Session::with('media', 'booth')->findOrFail($id);

        $data = [
            'title' => 'Session Details',
            'subtitle' => 'View session details and media',
            'session' => $session,
            'media' => $session->media,
            'booth' => $session->booth,
            'result' => $this->resultPayload($session),
        ];

        return view('sessions.show', $data);
    }

    /**
     * Data untuk menampilkan ulang HASIL AKHIR yang dipilih customer (frame +
     * background + foto per slot + filter). Null bila kiosk belum mengirim
     * susunan akhirnya, jadi halaman cukup menampilkan galeri biasa.
     */
    private function resultPayload(Session $session): ?array
    {
        $comp = $session->composition;
        if (!is_array($comp) || (empty($comp['cells']) && empty($comp['template']))) {
            return null;
        }

        $photos = $session->media
            ->sortBy('id')
            ->reject(fn ($m) => in_array($m->type, ['strip', 'gif', 'boomerang', 'video'], true))
            ->map(fn ($m) => asset(ltrim($m->path, '/')))
            ->values()
            ->all();

        if (empty($photos)) {
            return null;
        }

        // Background: diambil dari perpustakaan dashboard (bukan URL yang
        // tersimpan) supaya tetap tampil walau host/IP server berubah.
        $background = null;
        $bgId = $comp['background']['id'] ?? null;
        if ($bgId && ($bg = FrameBackground::find($bgId)) && $bg->image_path) {
            $background = [
                'url' => route('api.frame-backgrounds.image', ['background' => $bg->id], false)
                    . '?v=' . ($bg->updated_at?->timestamp ?? 0),
                'aspect' => $bg->aspect() ?? (float) ($comp['background']['aspect'] ?? 2 / 3),
                'area' => $bg->area,
            ];
        }

        // Template dari server: slot dari aplikasi, PNG frame dari database.
        $template = null;
        if (!empty($comp['template']) && is_array($comp['template'])) {
            $frame = !empty($comp['remote_frame_id']) ? PhotoFrame::find($comp['remote_frame_id']) : null;
            $template = [
                'canvas_w' => (float) ($comp['template']['canvas_w'] ?? 600),
                'canvas_h' => (float) ($comp['template']['canvas_h'] ?? 1800),
                'slots' => $comp['template']['slots'] ?? [],
                'image_url' => $frame?->image_url,
            ];
        }

        return [
            'photos' => $photos,
            'composition' => $comp,
            'background' => $template ? null : $background,
            'template' => $template,
            // Filter yang tersimpan (null = Natural).
            'filter' => array_key_exists('filter', $comp) ? ($comp['filter'] ?: 'none') : ($session->filter ?: 'none'),
            'save_url' => route('sessions.update', $session->id),
        ];
    }

    public function download($id)
    {
        $session = Session::with('media')->findOrFail($id);

        $zipName = 'session-' . $session->session_code . '.zip';
        $zipPath = storage_path('app/temp/' . $zipName);

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {

            foreach ($session->media as $media) {
                // API stores public media as "storage/sessions/...". Convert it to
                // its disk-relative path rather than depending on the web server.
                $path = ltrim(preg_replace('#^/?storage/#', '', $media->path), '/');

                if (Storage::disk('public')->exists($path)) {
                    $zip->addFromString(
                        $media->file_name ?? basename($path),
                        Storage::disk('public')->get($path)
                    );
                }
            }

            $zip->close();
        } else {
            abort(500, 'Could not create the session download.');
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'booth_id' => 'nullable|integer|exists:booths,id',
            'customer_name' => 'nullable|string|max:255',
            'layout' => 'nullable|string',
            'filter' => 'nullable|string',
            'frame_id' => 'nullable|integer|exists:photo_frames,id',
            'grid' => 'nullable|string',
        ]);

        $sessionCode = Str::upper(Str::random(8));

        $session = Session::create([
            'booth_id' => $data['booth_id'] ?? null,
            'session_code' => $sessionCode,
            'customer_name' => $data['customer_name'] ?? null,
            'total_files' => 0,
            'total_size' => 0,
            'taken_at' => now(),
        ]);

        $totalSize = 0;
        $count = 0;

        // Accept uploaded files (photos.*) or base64 array in `photos`.
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $file->getClientOriginalName());
                $path = $file->storeAs('sessions/' . $sessionCode, $fileName, 'public');
                $storedPath = 'storage/' . $path;

                $media = Media::create([
                    'session_id' => $session->id,
                    'type' => $file->getClientMimeType(),
                    'file_name' => $fileName,
                    'path' => $storedPath,
                    'size' => $file->getSize(),
                ]);

                $totalSize += $file->getSize();
                $count++;
            }
        } elseif ($request->has('photos') && is_array($request->input('photos'))) {
            foreach ($request->input('photos') as $i => $b64) {
                // expect data URL or pure base64
                if (preg_match('#^data:(image/[^;]+);base64,#', $b64, $m)) {
                    $ext = explode('/', $m[1])[1];
                    $dataPart = substr($b64, strpos($b64, ',') + 1);
                } else {
                    $ext = 'jpg';
                    $dataPart = $b64;
                }

                $decoded = base64_decode($dataPart);
                if ($decoded === false) continue;

                $fileName = $sessionCode . '_' . ($i + 1) . '.' . $ext;
                $relPath = 'sessions/' . $sessionCode . '/' . $fileName;
                Storage::disk('public')->put($relPath, $decoded);
                $size = strlen($decoded);

                Media::create([
                    'session_id' => $session->id,
                    'type' => 'image/' . $ext,
                    'file_name' => $fileName,
                    'path' => 'storage/' . $relPath,
                    'size' => $size,
                ]);

                $totalSize += $size;
                $count++;
            }
        }

        $session->update([
            'total_files' => $count,
            'total_size' => $totalSize,
        ]);

        return response()->json(['session' => $session->load('media')], 201);
    }

    public function update(Request $request, $id)
    {
        $session = Session::findOrFail($id);

        $data = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'layout' => 'nullable|string',
            'filter' => 'nullable|string',
            'frame_id' => 'nullable|integer|exists:photo_frames,id',
            'grid' => 'nullable|string',
        ]);

        // Filter dipilih ulang dari halaman detail: samakan dengan susunan akhir.
        if (array_key_exists('filter', $data) && is_array($session->composition)) {
            $data['composition'] = array_merge($session->composition, ['filter' => $data['filter']]);
        }

        $session->update($data);

        return response()->json(['session' => $session], 200);
    }

    public function destroy(Session $session)
    {
        // delete media files
        foreach ($session->media as $media) {
            $path = ltrim(preg_replace('#^/?storage/#', '', $media->path), '/');
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            $media->delete();
        }

        $session->delete();

        return response()->json(['message' => 'Session deleted'], 200);
    }

    public function print(Request $request, $id, SessionPrintService $printer)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $session = Session::with('media')->findOrFail($id);

        $result = $printer->dispatch($session, $request->input('email'));

        return response()->json(
            $result,
            ($result['email_sent'] || $result['print_dispatched']) ? 200 : 502
        );
    }
}
