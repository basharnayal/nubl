<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('provider_profiles', 'logo_path')) {
            return;
        }

        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: `logo_path` is created by `create_provider_profiles_table` on fresh installs.
        // `up()` skips when the column already exists; dropping here would remove that baseline
        // column on rollback. Legacy DBs cannot be distinguished safely.
    }
};