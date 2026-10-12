<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Frame Backgrounds sekarang hanya perpustakaan gambar background yang berlaku
 * untuk SEMUA frame. Baris lama yang terikat ke satu frame dijadikan background
 * umum (frame_key = 'all').
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('frame_backgrounds')
            ->where('frame_key', '!=', 'all')
            ->update(['frame_key' => 'all']);
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan: frame asal tiap background sudah tidak dicatat.
    }
};
