<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ewallets', function (Blueprint $table) {
            $table->id();

            $table->enum('owner_type', ['SYSTEM', 'PROVIDER']);

            // Nullable to allow SYSTEM wallets with no specific owner record
            $table->foreignId('owner_id')
                ->nullable()
                ->constrained('provider_profiles')
                ->nullOnDelete();

            $table->decimal('balance', 12, 2)->default(0);
            $table->boolean('status')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ewallets');
    }
};
