<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_payouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_wallet_id')->constrained('ewallets')->cascadeOnDelete();

            $table->dateTime('week_start_at');
            $table->dateTime('week_end_at');
            $table->dateTime('scheduled_at');

            $table->decimal('amount', 12, 2);

            $table->string('status', 40);

            $table->string('reference_number')->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('admin_note')->nullable();

            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->decimal('snapshot_wallet_balance', 12, 2)->nullable();
            $table->decimal('snapshot_available_amount', 12, 2)->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['provider_id', 'status']);
            $table->index(['week_start_at', 'week_end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_payouts');
    }
};
