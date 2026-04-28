<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained('provider_menu_items')->cascadeOnDelete();

            // Request Details
            $table->integer('quantity');
            $table->decimal('price_snapshot', 10, 2); // Snapshot of price at time of request
            $table->string('request_type'); // e.g., 'meal', 'basket' - from menu_item.category

            // Status & Payment
            $table->string('status')->default('REQUESTED'); // REQUESTED, APPROVED, REJECTED, REDEEMABLE, FULFILLED
            $table->boolean('is_flagged')->default(false);
            $table->string('invoice_id')->nullable();
            $table->string('payment_url')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
