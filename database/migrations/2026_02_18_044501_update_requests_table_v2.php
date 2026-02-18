<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // Add V2 Columns
            $table->decimal('reserved_amount', 10, 2)->after('id')->default(0);
            $table->string('funding_source')->nullable()->after('reserved_amount'); // CITY_FUND, PROVIDER_ADOPTION
            $table->string('rejection_reason_code')->nullable()->after('status');
            $table->text('rejection_reason_note')->nullable()->after('rejection_reason_code');

            // Remove Old Columns (Make nullable first if we were keeping data, but here we drop)
            $table->dropForeign(['menu_item_id']);
            $table->dropColumn(['menu_item_id', 'quantity', 'price_snapshot', 'request_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // Restore Old Columns
            $table->foreignId('menu_item_id')->nullable()->constrained('provider_menu_items')->cascadeOnDelete();
            $table->integer('quantity')->nullable();
            $table->decimal('price_snapshot', 10, 2)->nullable();
            $table->string('request_type')->nullable();

            // Drop V2 Columns
            $table->dropColumn(['reserved_amount', 'funding_source', 'rejection_reason_code', 'rejection_reason_note']);
        });
    }
};
