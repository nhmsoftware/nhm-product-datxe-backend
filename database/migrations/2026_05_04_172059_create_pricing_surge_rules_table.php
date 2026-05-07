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
        Schema::create('pricing_surge_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->integer('vehicle_type');
            $table->json('conditions'); // e.g., ["peak_hour", "weather_rain"]
            $table->decimal('multiplier', 5, 2);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('area_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_surge_rules');
    }
};
