<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recipient profiles no longer store a profile logo path (same as schema without the column).
     */
    public function up(): void
    {
        if (Schema::hasTable('recipient_profiles') && Schema::hasColumn('recipient_profiles', 'logo_path')) {
            Schema::table('recipient_profiles', function (Blueprint $table) {
                $table->dropColumn('logo_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('recipient_profiles') && ! Schema::hasColumn('recipient_profiles', 'logo_path')) {
            Schema::table('recipient_profiles', function (Blueprint $table) {
                $table->string('logo_path', 512)->nullable()->after('id_photo_path');
            });
        }
    }
};
