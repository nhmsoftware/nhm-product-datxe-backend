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
        Schema::create('pricing_configs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->integer('vehicle_type')->unique();
            $table->decimal('base_price', 15, 2);
            $table->decimal('distance_rate', 15, 2);
            $table->decimal('time_rate', 15, 2);
            $table->decimal('min_fare', 15, 2);
            $table->decimal('surge_multiplier', 5, 2)->default(1.0);
            $table->decimal('commission_rate', 5, 2)->default(20.0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_configs');
    }
};
