<?php

namespace App\Http\Controllers;

use App\Models\Session;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function show($id)
    {
        $session = Session::with('media', 'booth')->findOrFail($id);

        return view('sessions.show', compact('session'));
    }

    public function download($id)
    {
        $session = Session::with('media')->findOrFail($id);

        $zipName = 'session-' . $session->id . '.zip';
        $zipPath = storage_path($zipName);

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {

            foreach ($session->media as $media) {

                // ambil file dari URL
                $fileContent = file_get_contents($media->path);

                $zip->addFromString($media->file_name ?? 'file.jpg', $fileContent);
            }

            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
