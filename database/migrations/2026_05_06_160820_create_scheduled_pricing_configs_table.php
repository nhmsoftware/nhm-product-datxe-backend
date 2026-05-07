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
        Schema::create('scheduled_pricing_configs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->decimal('base_price', 15, 2)->default(0);
            $table->decimal('scheduled_surcharge', 15, 2)->default(0);
            $table->decimal('intercity_base_price', 15, 2)->default(0);
            $table->decimal('airport_base_price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_pricing_configs');
    }
};
