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
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('service_type')->comment('1: Ride, 2: Food, 3: Delivery');
            $table->unsignedTinyInteger('scope')->default(1)->comment('1: System, 2: Regional');
            $table->string('area_id')->nullable()->comment('ID khu vực nếu scope là Regional');
            
            $table->decimal('commission_rate', 5, 2)->comment('Tỷ lệ hoa hồng (%)');
            $table->decimal('min_commission', 15, 2)->nullable()->comment('Hoa hồng tối thiểu');
            $table->decimal('max_commission', 15, 2)->nullable()->comment('Hoa hồng tối đa');
            
            $table->boolean('is_active')->default(true);
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['service_type', 'scope', 'is_active']);
            $table->index(['area_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
