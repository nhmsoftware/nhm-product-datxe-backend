<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name_vi', 100);
            $table->string('description_vi', 255)->nullable();
            $table->unsignedTinyInteger('capacity')->default(1);
            $table->string('estimated_wait_time', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};
