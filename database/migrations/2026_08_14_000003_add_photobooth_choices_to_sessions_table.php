<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('photo_frame_id')->nullable()->after('booth_id')
                ->constrained('photo_frames')->nullOnDelete();
            $table->string('layout')->nullable()->after('customer_name');
            $table->string('grid')->nullable()->after('layout');
            $table->string('filter')->nullable()->after('grid');
            $table->string('email')->nullable()->after('filter');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('photo_frame_id');
            $table->dropColumn(['layout', 'grid', 'filter', 'email']);
        });
    }
};
