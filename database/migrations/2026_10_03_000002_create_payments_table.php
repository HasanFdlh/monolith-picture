<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // order_id dikirim ke Midtrans dan dikembalikan lewat callback.
            $table->string('order_id')->unique();
            $table->foreignId('session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('photo_package_id')->nullable()->constrained('photo_packages')->nullOnDelete();
            $table->unsignedInteger('takes')->default(0);
            $table->decimal('amount', 12, 2);
            // pending, settlement, capture, expire, cancel, deny, refund, failure
            $table->string('status')->default('pending');
            $table->string('payment_type')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('snap_token')->nullable();
            $table->text('snap_redirect_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
