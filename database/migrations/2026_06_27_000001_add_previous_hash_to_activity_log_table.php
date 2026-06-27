<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-13.2: Add previous_hash column to activity_log to enable hash chaining.
 *
 * Nullable because existing rows pre-date the chain; run audit:rebuild-chain
 * after this migration to back-fill and seal all historical rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table) {
                $table->string('previous_hash', 64)->nullable()->after('sha256_hash');
            });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table) {
                $table->dropColumn('previous_hash');
            });
    }
};
