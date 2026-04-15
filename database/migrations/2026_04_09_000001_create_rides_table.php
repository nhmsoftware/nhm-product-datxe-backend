<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('pickup_address');
            $table->decimal('pickup_lat', 10, 7);
            $table->decimal('pickup_lng', 10, 7);
            
            $table->string('destination_address');
            $table->decimal('destination_lat', 10, 7);
            $table->decimal('destination_lng', 10, 7);
            
            $table->unsignedBigInteger('distance')->comment('Distance in meters');
            $table->unsignedBigInteger('duration')->comment('Duration in seconds');
            
            $table->unsignedTinyInteger('vehicle_type');
            $table->unsignedTinyInteger('status')->default(1)->comment('1: Draft, 2: Pending, 3: Accepted, 4: In Progress, 5: Completed, 6: Cancelled');
            
            $table->decimal('base_price', 15, 2)->default(0);
            $table->decimal('distance_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->boolean('is_paid')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['customer_id', 'status']);
            $table->index(['driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rides');
    }
};
