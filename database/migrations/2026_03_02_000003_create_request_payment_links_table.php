<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_payment_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')
                ->constrained('payments')
                ->cascadeOnDelete();

            $table->foreignId('request_id')
                ->constrained('requests')
                ->cascadeOnDelete();

            $table->decimal('amount', 10, 2);

            $table->timestamps();

            $table->index('payment_id');
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_payment_links');
    }
};
