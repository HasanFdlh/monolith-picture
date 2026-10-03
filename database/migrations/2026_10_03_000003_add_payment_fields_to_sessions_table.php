<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('photo_package_id')->nullable()->after('photo_frame_id')
                ->constrained('photo_packages')->nullOnDelete();
            // Berapa foto yang boleh diambil pada sesi ini (dari paket yang dibayar).
            $table->unsignedInteger('allowed_takes')->default(0)->after('photo_package_id');
            // unpaid, pending, paid, expired, failed
            $table->string('payment_status')->default('unpaid')->after('allowed_takes');
            $table->timestamp('paid_at')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('photo_package_id');
            $table->dropColumn(['allowed_takes', 'payment_status', 'paid_at']);
        });
    }
};
