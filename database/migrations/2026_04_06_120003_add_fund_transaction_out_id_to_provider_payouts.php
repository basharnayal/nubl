<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_payouts', function (Blueprint $table) {
            $table->unsignedBigInteger('fund_transaction_out_id')->nullable()->after('meta');
            $table->foreign('fund_transaction_out_id')
                ->references('id')
                ->on('fund_transactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('provider_payouts', function (Blueprint $table) {
            $table->dropForeign(['fund_transaction_out_id']);
            $table->dropColumn('fund_transaction_out_id');
        });
    }
};
