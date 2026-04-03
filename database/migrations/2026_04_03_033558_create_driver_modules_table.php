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
        Schema::create('driver_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('full_name', 100);
            $table->foreignId('driver_group_id')->nullable()->constrained('driver_groups');
            $table->unsignedTinyInteger('driver_group_type'); // DriverGroupType Enum
            $table->unsignedTinyInteger('vehicle_type'); // VehicleType Enum
            $table->string('vehicle_name', 255);
            $table->unsignedTinyInteger('vehicle_color'); // VehicleColor Enum
            $table->string('vehicle_number', 255);
            $table->boolean('is_online')->default(false);
            $table->decimal('current_lat', 10, 7)->nullable();
            $table->decimal('current_lng', 10, 7)->nullable();
            $table->unsignedTinyInteger('status')->default(1); // DriverStatus Enum
            $table->timestamp('cooldown_until')->nullable();
            $table->unsignedSmallInteger('cancel_count_today')->default(0);
            $table->timestamps();

            $table->index(['driver_group_type', 'is_online', 'status']);
            $table->index(['status', 'cooldown_until']);
            $table->index('vehicle_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_modules');
    }
};
