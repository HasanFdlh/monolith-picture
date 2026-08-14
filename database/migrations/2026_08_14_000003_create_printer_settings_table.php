<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('printer_settings', function (Blueprint $table) {
            $table->id();
            $table->string('printer_name')->nullable();
            $table->string('printer_command')->nullable();
            $table->integer('copies')->default(1);
            $table->string('paper_size')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('printer_settings');
    }
};
