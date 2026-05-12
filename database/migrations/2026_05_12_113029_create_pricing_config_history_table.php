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
        Schema::create('pricing_config_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->integer('vehicle_type');
            $table->json('old_config')->nullable();
            $table->json('new_config');
            $table->ulid('admin_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_config_history');
    }
};
