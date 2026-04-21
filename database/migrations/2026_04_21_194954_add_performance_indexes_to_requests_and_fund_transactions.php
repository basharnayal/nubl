<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->index('source');
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropIndex(['direction']);
        });
    }
};
