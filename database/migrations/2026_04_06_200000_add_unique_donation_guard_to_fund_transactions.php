<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a unique partial index on (payment_id, direction) for DONATION fund
 * transactions. This is a database-level safety net that guarantees a single
 * IN fund-transaction per payment, even if application-level idempotency
 * checks fail due to race conditions.
 *
 * Why a composite unique index instead of unique on payment_id alone?
 * - payment_id is nullable (non-donation transactions have null payment_id)
 * - A future refund flow may create an OUT transaction linked to the same payment_id
 * - The composite (payment_id, direction, source) keeps the constraint tight
 *   without blocking legitimate non-donation uses of payment_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            // Prevents duplicate donation credits for the same payment.
            // MySQL/MariaDB allow multiple NULLs in unique indexes, so
            // non-donation transactions (payment_id = null) are unaffected.
            $table->unique(
                ['payment_id', 'direction', 'source'],
                'fund_tx_payment_direction_source_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->dropUnique('fund_tx_payment_direction_source_unique');
        });
    }
};
