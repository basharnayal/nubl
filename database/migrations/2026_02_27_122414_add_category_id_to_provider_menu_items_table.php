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
        Schema::table('provider_menu_items', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('menu_item_categories')->nullOnDelete();
            $table->index(['provider_id', 'category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_menu_items', function (Blueprint $table) {
            $table->dropIndex(['provider_id', 'category_id', 'is_active']);
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
