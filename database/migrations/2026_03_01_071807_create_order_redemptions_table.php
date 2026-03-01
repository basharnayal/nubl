<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_code')->index();
            $table->text('token_ciphertext')->nullable();
            $table->string('token_qr_url')->nullable();
            $table->integer('ttl_minutes')->default(180);
            $table->timestamp('redeem_expires_at');
            $table->enum('status', ['PENDING', 'REDEEMED', 'EXPIRED'])->default('PENDING');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_redemptions');
    }
};
