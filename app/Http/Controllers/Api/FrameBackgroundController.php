<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FrameBackground;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FrameBackgroundController extends Controller
{
    /**
     * GET /api/frame-backgrounds
     * Desain background untuk frame bawaan Flutter. Satu `frame_key` boleh
     * muncul beberapa kali (satu baris per desain, urut seperti di dashboard).
     * Desain "Semua Frame" (frame_key `all`) dikirim sekali untuk tiap frame
     * dengan `universal: true`.
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

        $query = FrameBackground::query()
            ->where('is_active', true)
            ->whereNotNull('image_path')
            ->orderBy('frame_key');

        // Migrasi multi-desain belum dijalankan -> urut berdasarkan id saja.
        if (Schema::hasColumn('frame_backgrounds', 'sort_order')) {
            $query->ordered();
        } else {
            $query->orderBy('id');
        }

        $rows = $query->get();

        $toArray = fn (FrameBackground $row, string $frameKey, bool $universal) => [
            'id' => $row->id,
            'frame_key' => $frameKey,
            // Nama desain (customer memilihnya sebelum memilih filter).
            'name' => $row->name,
            'image_url' => $row->image_url,
            'width' => $row->width,
            'height' => $row->height,
            // Kotak tempat foto ditempatkan (persen dari lembar).
            'area' => $row->area,
            // true = desain "Semua Frame" (upload sekali, berlaku di semua frame).
            'universal' => $universal,
        ];

        $specific = $rows->reject(fn (FrameBackground $r) => $r->frame_key === FrameBackground::UNIVERSAL_KEY);
        $universal = $rows->filter(fn (FrameBackground $r) => $r->frame_key === FrameBackground::UNIVERSAL_KEY);

        // Desain khusus-frame lebih dulu, lalu desain "Semua Frame" digandakan
        // untuk setiap frame bawaan (aplikasi mengelompokkan per frame_key
        // dengan urutan kemunculan).
        $data = $specific
            ->map(fn (FrameBackground $r) => $toArray($r, $r->frame_key, false))
            ->concat(
                collect(FrameBackground::CATALOG)->flatMap(
                    fn (array $frame) => $universal->map(
                        fn (FrameBackground $r) => $toArray($r, $frame['key'], true)
                    )
                )
            )
            ->values();

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/frame-backgrounds/{background}/image
     * Mengirim file gambar desain langsung dari disk `public`, tanpa
     * bergantung pada symlink public/storage.
     */
    public function image(FrameBackground $background)
    {
        $path = ltrim((string) $background->image_path, '/');

        abort_if($path === '' || !Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            // URL membawa ?v=<updated_at>, jadi aman di-cache lama.
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
