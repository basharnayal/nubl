<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_financial_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('iban');
            $table->string('account_holder_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_financial_info');
    }
};
