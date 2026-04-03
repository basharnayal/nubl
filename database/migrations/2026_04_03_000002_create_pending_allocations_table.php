<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('provider_id');
            $table->decimal('amount', 10, 2);
            $table->enum('paused_by', ['global', 'provider'])->default('global');
            $table->timestamps();

            $table->foreign('request_id')->references('id')->on('requests')->cascadeOnDelete();
            $table->foreign('provider_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_allocations');
    }
};
