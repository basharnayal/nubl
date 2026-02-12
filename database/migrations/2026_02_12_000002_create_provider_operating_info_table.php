<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Operating hours: JSON per day.
     * Format: { "sunday": {"open":"09:00","close":"17:00","closed":false}, "monday": {"closed":true}, ... }
     */
    public function up(): void
    {
        Schema::create('provider_operating_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('operating_hours'); // per-day: {day: {open, close, closed}}
            $table->unsignedInteger('daily_capacity');
            $table->json('service_type'); // ['meal_preparation', 'delivery', ...]
            $table->string('estimated_preparation_order_time'); // e.g. "30 minutes"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_operating_info');
    }
};
