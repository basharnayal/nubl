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

            $table->foreignId('sponsor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('gateway', 50)->default('MYFATOORAH');
            $table->string('external_payment_id')->nullable();
            $table->enum('status', ['INITIATED', 'PENDING', 'PROCESSING', 'SUCCEEDED', 'FAILED']);
            $table->decimal('amount', 10, 2);
            $table->json('notes')->nullable();
            $table->uuid('idempotency_key')->nullable();

            $table->timestamps();

            $table->unique('idempotency_key');
            $table->index('external_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
