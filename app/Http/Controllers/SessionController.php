<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\SessionPrintMail;

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
        ];

        return view('sessions.show', $data);
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

    public function print(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $session = Session::with('media')->findOrFail($id);

        $zipName = 'session-' . $session->session_code . '.zip';
        $zipPath = storage_path('app/temp/' . $zipName);

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($session->media as $media) {
                $path = ltrim(preg_replace('#^/?storage/#', '', $media->path), '/');
                if (Storage::disk('public')->exists($path)) {
                    $zip->addFromString($media->file_name ?? basename($path), Storage::disk('public')->get($path));
                }
            }
            $zip->close();
        } else {
            abort(500, 'Could not create the session print package.');
        }

        // send email with attachment
        try {
            Mail::to($request->input('email'))->send(new SessionPrintMail($session, $zipPath));
        } catch (\Exception $e) {
            // continue but report error
            return response()->json(['error' => 'Failed to send email: ' . $e->getMessage()], 500);
        }

        // optional auto-print: run a configured command replacing {file}
        $printerCmd = config('photobooth.printer_command');
        if ($printerCmd) {
            $cmd = str_replace('{file}', escapeshellarg($zipPath), $printerCmd);
            @exec($cmd . ' > /dev/null 2>&1 &');
        }

        return response()->json(['message' => 'Print job started and email sent'], 200);
    }
}
