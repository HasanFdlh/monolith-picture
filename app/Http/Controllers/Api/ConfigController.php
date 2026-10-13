<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ConfigController extends Controller
{
    /**
     * GET /api/config
     *
     * - `require_payment` / `shots_per_session`: pengaturan yang harus diikuti
     *   kiosk Flutter (server = sumber kebenaran).
     * - `warnings`: masalah setup server yang bikin gambar/fitur tidak jalan,
     *   supaya terlihat dari aplikasi (Test Connection) dan bukan baru ketahuan
     *   lewat 404/500.
     */
    public function show(): JsonResponse
    {
        return response()->json(['data' => [
            'require_payment' => (bool) config('photobooth.require_payment'),
            'shots_per_session' => (int) config('photobooth.shots_per_session', 8),
            'warnings' => $this->warnings(),
        ]]);
    }

    /** @return list<string> */
    private function warnings(): array
    {
        $warnings = [];

        if (!file_exists(public_path('storage'))) {
            $warnings[] = 'public/storage belum ada, gambar frame & foto akan 404. Jalankan: php artisan storage:link';
        }

        try {
            if (!Schema::hasTable('frame_backgrounds')) {
                $warnings[] = 'Tabel frame_backgrounds belum ada. Jalankan: php artisan migrate';
            }
        } catch (Throwable) {
            // koneksi DB bermasalah akan terlihat di endpoint lain
        }

        try {
            if (Schema::hasTable('frame_backgrounds') && !Schema::hasColumn('frame_backgrounds', 'sort_order')) {
                $warnings[] = 'Migrasi desain background belum dijalankan (1 frame = 1 desain). Jalankan: php artisan migrate';
            }
        } catch (Throwable) {
            // sama seperti di atas
        }

        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $warnings[] = 'MAIL_MAILER=' . config('mail.default') . ': email hasil foto tidak benar-benar terkirim.';
        }

        if (!config('photobooth.printer_command')) {
            $warnings[] = 'PHOTOBOOTH_PRINTER_COMMAND kosong: tidak ada perintah cetak otomatis.';
        }

        return $warnings;
    }
}
