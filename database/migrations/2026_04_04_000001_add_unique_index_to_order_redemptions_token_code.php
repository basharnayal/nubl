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
        Schema::table('order_redemptions', function (Blueprint $table) {
            $table->dropIndex(['token_code']);
            $table->unique('token_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_redemptions', function (Blueprint $table) {
            $table->dropUnique(['token_code']);
            $table->index('token_code');
        });
    }
};
