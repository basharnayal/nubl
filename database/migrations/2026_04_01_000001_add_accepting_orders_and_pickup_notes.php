<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('accepting_orders')->default(true);
        });

        Schema::table('provider_operating_info', function (Blueprint $table) {
            $table->text('pickup_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('provider_operating_info', function (Blueprint $table) {
            $table->dropColumn('pickup_notes');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('accepting_orders');
        });
    }
};
