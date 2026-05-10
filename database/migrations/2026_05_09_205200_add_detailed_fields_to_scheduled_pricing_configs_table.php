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
        Schema::table('scheduled_pricing_configs', function (Blueprint $table) {
            // Detailed Intercity
            $table->decimal('intercity_distance_rate', 15, 2)->default(0)->after('intercity_base_price');
            $table->decimal('intercity_time_rate', 15, 2)->default(0)->after('intercity_distance_rate');
            $table->decimal('intercity_min_fare', 15, 2)->default(0)->after('intercity_time_rate');

            // Detailed Airport
            $table->decimal('airport_distance_rate', 15, 2)->default(0)->after('airport_base_price');
            $table->decimal('airport_time_rate', 15, 2)->default(0)->after('airport_distance_rate');
            $table->decimal('airport_min_fare', 15, 2)->default(0)->after('airport_time_rate');

            // Detailed Delivery
            $table->decimal('delivery_base_price', 15, 2)->default(0)->after('airport_min_fare');
            $table->decimal('delivery_distance_rate', 15, 2)->default(0)->after('delivery_base_price');
            $table->decimal('delivery_time_rate', 15, 2)->default(0)->after('delivery_distance_rate');
            $table->decimal('delivery_min_fare', 15, 2)->default(0)->after('delivery_time_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheduled_pricing_configs', function (Blueprint $table) {
            $table->dropColumn([
                'intercity_distance_rate', 'intercity_time_rate', 'intercity_min_fare',
                'airport_distance_rate', 'airport_time_rate', 'airport_min_fare',
                'delivery_base_price', 'delivery_distance_rate', 'delivery_time_rate', 'delivery_min_fare'
            ]);
        });
    }
};
