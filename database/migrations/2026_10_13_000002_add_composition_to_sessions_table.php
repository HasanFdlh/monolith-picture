<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            // Susunan hasil akhir dari kiosk: frame, background, foto per sel,
            // dan filter. Dipakai halaman detail sesi untuk menampilkan ulang
            // hasil yang dipilih customer.
            $table->json('composition')->nullable()->after('filter');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('composition');
        });
    }
};
