<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->boolean('is_anonymous')->default(false)->after('is_guest');
            $table->index(
                ['status', 'is_anonymous', 'is_guest', 'sponsor_id'],
                'payments_top_donors_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_top_donors_idx');
            $table->dropColumn('is_anonymous');
        });
    }
};
