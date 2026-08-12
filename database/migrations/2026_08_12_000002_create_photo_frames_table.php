<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_frames', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('All');
            $table->string('scope')->default('Generic');
            $table->string('status')->default('active');
            $table->string('print_size')->default('Normal');
            $table->string('printer_setting')->default('Primary Printer');
            $table->string('file_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('layout_json')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_frames');
    }
};
