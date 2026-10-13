<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            // Terakhir kali sesi ini dicetak dari kiosk (null = belum pernah dicetak).
            $table->timestamp('printed_at')->nullable()->after('paid_at');
            // Berapa kali tombol Cetak dipakai pada sesi ini.
            $table->unsignedInteger('print_count')->default(0)->after('printed_at');
            $table->index('printed_at');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex(['printed_at']);
            $table->dropColumn(['printed_at', 'print_count']);
        });
    }
};
