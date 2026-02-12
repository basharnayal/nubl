<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_operating_info', function (Blueprint $table) {
            $table->string('adoption_support', 20)->nullable()->after('estimated_preparation_order_time');
        });
    }

    public function down(): void
    {
        Schema::table('provider_operating_info', function (Blueprint $table) {
            $table->dropColumn('adoption_support');
        });
    }
};
