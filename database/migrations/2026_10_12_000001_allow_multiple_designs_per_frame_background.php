<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satu frame bawaan sekarang boleh punya BANYAK desain background
 * (customer memilihnya di aplikasi sebelum memilih filter).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frame_backgrounds', function (Blueprint $table) {
            // frame_key tidak lagi unik: satu frame, banyak desain.
            $table->dropUnique(['frame_key']);
            $table->index('frame_key');

            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        // Baris lama = desain pertama. `name` dulu berisi nama frame, sekarang nama desain.
        DB::table('frame_backgrounds')->update(['name' => 'Desain 1']);
    }

    public function down(): void
    {
        // Kembali ke 1 desain per frame: simpan yang paling awal saja.
        $keep = DB::table('frame_backgrounds')
            ->selectRaw('MIN(id) as id')
            ->groupBy('frame_key')
            ->pluck('id');
        DB::table('frame_backgrounds')->whereNotIn('id', $keep)->delete();

        Schema::table('frame_backgrounds', function (Blueprint $table) {
            $table->dropIndex(['frame_key']);
            $table->unique('frame_key');
            $table->dropColumn('sort_order');
        });
    }
};
