<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Jumlah foto ("takes") yang didapat customer setelah bayar.
            $table->unsignedInteger('takes');
            $table->decimal('price', 12, 2);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_packages');
    }
};
