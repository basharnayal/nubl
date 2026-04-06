<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->foreignId('provider_payout_id')
                ->nullable()
                ->after('order_redemption_id')
                ->constrained('provider_payouts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->dropForeign(['provider_payout_id']);
            $table->dropColumn('provider_payout_id');
        });
    }
};
