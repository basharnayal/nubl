<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('sponsor_id')->nullable()->change();
            $table->boolean('is_guest')->default(false)->after('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('is_guest');
            $table->unsignedBigInteger('sponsor_id')->nullable(false)->change();
        });
    }
};
