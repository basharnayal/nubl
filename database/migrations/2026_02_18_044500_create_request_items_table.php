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
        Schema::create('request_items', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained('provider_menu_items')->cascadeOnDelete();

            // Item Details
            $table->integer('quantity');
            $table->decimal('price_snapshot', 10, 2); // Price at time of request
            $table->decimal('line_total', 10, 2)->virtualAs('price_snapshot * quantity'); // Virtual column for convenience if DB supports, else calculated

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_items');
    }
};
