<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipient_kyc_details', function (Blueprint $table) {
            $table->string('employment_status', 50)->nullable()->after('is_student');
            $table->text('situation_description')->nullable()->after('employment_status');
        });
    }

    public function down(): void
    {
        Schema::table('recipient_kyc_details', function (Blueprint $table) {
            $table->dropColumn(['employment_status', 'situation_description']);
        });
    }
};
