<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds referential integrity for fund_transactions.order_redemption_id -> order_redemptions.id.
 *
 * Before running on shared/production DB, ensure there are no orphaned rows, e.g.:
 *   SELECT ft.id, ft.order_redemption_id
 *   FROM fund_transactions ft
 *   LEFT JOIN order_redemptions orx ON orx.id = ft.order_redemption_id
 *   WHERE ft.order_redemption_id IS NOT NULL AND orx.id IS NULL;
 * Fix or SET order_redemption_id = NULL on those rows before migrating.
 *
 * nullOnDelete() matches fund_transactions.payment_id and allows order_redemptions rows
 * to be removed (e.g. cascade when a request is deleted) without deleting ledger rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->foreign('order_redemption_id')
                ->references('id')
                ->on('order_redemptions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->dropForeign(['order_redemption_id']);
        });
    }
};
