<?php

namespace App\Http\Controllers;

use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
}
