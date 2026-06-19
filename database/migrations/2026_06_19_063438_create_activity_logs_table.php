<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // User yang melakukan aksi
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Nama module
            $table->string('module', 100);

            // Aksi yang dilakukan
            $table->string('action', 50);

            // ID data yang diproses
            $table->unsignedBigInteger('record_id')->nullable();

            // Keterangan
            $table->text('description')->nullable();

            // Data sebelum perubahan
            $table->json('old_values')->nullable();

            // Data setelah perubahan
            $table->json('new_values')->nullable();

            // Informasi client
            $table->ipAddress('ip_address')->nullable();

            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index('module');
            $table->index('action');
            $table->index('record_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
