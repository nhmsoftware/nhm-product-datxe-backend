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
        Schema::create('merchant_opening_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_profile_id')->constrained('merchant_profiles')->onDelete('cascade');
            $table->tinyInteger('day_of_week')->comment('1: Monday, ..., 7: Sunday');
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->boolean('is_overnight')->default(false);
            $table->timestamps();

            $table->unique(['merchant_profile_id', 'day_of_week'], 'merchant_day_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_opening_hours');
    }
};
