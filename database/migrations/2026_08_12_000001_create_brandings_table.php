<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brandings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->default('Your Photobooth Business');
            $table->string('tagline')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('logo_url')->nullable();
            $table->text('custom_message')->nullable();
            $table->string('primary_color')->default('#4f46e5');
            $table->string('secondary_color')->default('#8b5cf6');
            $table->string('background_style')->default('gradient');
            $table->string('background_gradient')->default('purple');
            $table->string('instagram_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('website_url')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_person')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brandings');
    }
};
