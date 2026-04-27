<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipient_profiles', function (Blueprint $table) {
            $table->string('id_number', 20)->nullable()->after('id_type');
            $table->text('location')->nullable()->after('short_address');
        });
    }

    public function down(): void
    {
        Schema::table('recipient_profiles', function (Blueprint $table) {
            $table->dropColumn(['id_number', 'location']);
        });
    }
};
