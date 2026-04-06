<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_payout_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_payout_id')->constrained('provider_payouts')->cascadeOnDelete();
            $table->foreignId('fund_transaction_id')->constrained('fund_transactions')->restrictOnDelete();

            $table->decimal('amount', 12, 2);

            $table->timestamps();

            $table->index('fund_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_payout_items');
    }
};
