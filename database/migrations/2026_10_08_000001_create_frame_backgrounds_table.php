<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frame_backgrounds', function (Blueprint $table) {
            $table->id();
            // ID frame bawaan di aplikasi Flutter (mis. "main-character").
            $table->string('frame_key')->unique();
            $table->string('name');
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frame_backgrounds');
    }
};
