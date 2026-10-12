<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FrameBackground;
use Illuminate\Support\Facades\Schema;

class FrameBackgroundController extends Controller
{
    /**
     * GET /api/frame-backgrounds
     * Background untuk frame bawaan Flutter, dikunci dengan `frame_key`.
     */
    public function index()
    {
        // Migrasi belum dijalankan -> balas kosong (frame bawaan Flutter tetap
        // pakai tampilan default) alih-alih 500.
        if (!Schema::hasTable('frame_backgrounds')) {
            return response()->json([
                'data' => [],
                'warning' => 'Tabel frame_backgrounds belum ada. Jalankan: php artisan migrate',
            ]);
        }

        $data = FrameBackground::query()
            ->where('is_active', true)
            ->whereNotNull('image_path')
            ->get()
            ->map(fn (FrameBackground $row) => [
                'frame_key' => $row->frame_key,
                'name' => $row->name,
                'image_url' => $row->image_url,
                'width' => $row->width,
                'height' => $row->height,
                // Kotak tempat foto ditempatkan (persen dari lembar).
                'area' => $row->area,
            ])
            ->values();

        return response()->json(['data' => $data]);
    }
}
