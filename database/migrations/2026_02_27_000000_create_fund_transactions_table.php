<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')
                ->constrained('ewallets')
                ->cascadeOnDelete();

            $table->foreignId('sponsor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('source'); // DONATION, REDEMPTION, REFUND, EXPIRY_ROLLBACK, CANCELLED, PAYOUT
            $table->decimal('amount', 12, 2);

            $table->enum('direction', ['IN', 'OUT']);

            $table->unsignedBigInteger('payment_id')->nullable();
            $table->foreignId('request_id')
                ->nullable()
                ->constrained('requests')
                ->nullOnDelete();
            $table->unsignedBigInteger('order_redemption_id')->nullable();

            $table->timestamps();

            // Add foreign keys when payments and order_redemptions tables exist
            // $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
            // $table->foreign('order_redemption_id')->references('id')->on('order_redemptions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transactions');
    }
};
