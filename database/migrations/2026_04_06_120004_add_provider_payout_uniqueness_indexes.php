<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Defense-in-depth against duplicate ledger lines per payout and duplicate OUT links.
     *
     * Note: The same fund_transaction may appear in items for different provider_payouts
     * after a rejected/cancelled payout (retry); only duplicates within one payout are forbidden.
     */
    public function up(): void
    {
        Schema::table('provider_payout_items', function (Blueprint $table) {
            $table->unique(['provider_payout_id', 'fund_transaction_id'], 'provider_payout_items_payout_ft_unique');
        });

        Schema::table('provider_payouts', function (Blueprint $table) {
            $table->unique('fund_transaction_out_id', 'provider_payouts_fund_transaction_out_unique');
        });
    }

    public function down(): void
    {
        Schema::table('provider_payout_items', function (Blueprint $table) {
            $table->dropUnique('provider_payout_items_payout_ft_unique');
        });

        Schema::table('provider_payouts', function (Blueprint $table) {
            $table->dropUnique('provider_payouts_fund_transaction_out_unique');
        });
    }
};
