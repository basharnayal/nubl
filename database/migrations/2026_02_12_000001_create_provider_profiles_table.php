<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name_ar');
            $table->string('full_name_en');
            $table->string('phone_number', 20);
            $table->string('email');
            $table->string('business_name_ar');
            $table->string('business_name_en');
            $table->string('unified_number');
            $table->json('business_category'); // ['restaurant', 'catering', ...]
            $table->text('address_ar');
            $table->text('address_en');
            $table->string('city');
            $table->string('region');
            $table->string('location')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_profiles');
    }
};
