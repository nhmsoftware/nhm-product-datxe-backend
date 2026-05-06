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
        Schema::create('scheduled_ride_cancellation_configs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedTinyInteger('ride_type')->comment('1: CITY, 2: INTERCITY, 3: AIRPORT');
            $table->integer('min_minutes_before_pickup')->default(0)->comment('Threshold for this rule');
            $table->unsignedTinyInteger('fee_type')->comment('1: FIXED, 2: PERCENTAGE');
            $table->decimal('fee_value', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ride_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_ride_cancellation_configs');
    }
};
