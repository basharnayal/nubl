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
            $table->string('short_code_hash', 64)->after('token_code')->nullable()->index();
            $table->text('short_code_ciphertext')->after('short_code_hash')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_redemptions', function (Blueprint $table) {
            $table->dropColumn(['short_code_hash', 'short_code_ciphertext']);
        });
    }
};
