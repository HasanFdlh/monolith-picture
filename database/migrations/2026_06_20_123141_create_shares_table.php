<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained()->cascadeOnDelete();
            $table->enum('platform', ['download', 'whatsapp', 'instagram', 'email', 'line',]);
            $table->timestamp('shared_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
