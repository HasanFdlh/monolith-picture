<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frame_backgrounds', function (Blueprint $table) {
            // Ukuran asli gambar (menentukan rasio lembar di aplikasi).
            $table->unsignedInteger('width')->nullable()->after('image_path');
            $table->unsignedInteger('height')->nullable()->after('width');
            // Area tempat foto ditempatkan, dalam persen dari lembar.
            $table->float('area_x')->nullable()->after('height');
            $table->float('area_y')->nullable()->after('area_x');
            $table->float('area_w')->nullable()->after('area_y');
            $table->float('area_h')->nullable()->after('area_w');
        });
    }

    public function down(): void
    {
        Schema::table('frame_backgrounds', function (Blueprint $table) {
            $table->dropColumn(['width', 'height', 'area_x', 'area_y', 'area_w', 'area_h']);
        });
    }
};
