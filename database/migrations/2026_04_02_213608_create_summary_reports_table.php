<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-19.1: Stores auto-generated weekly and monthly summary reports so admins
 * can view and download them without re-running the query.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('summary_reports', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);        // 'weekly' | 'monthly'
            $table->date('period_from');
            $table->date('period_to');
            $table->json('payload');            // serialized summary array
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['type', 'period_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('summary_reports');
    }
};
