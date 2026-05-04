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
        Schema::table('rides', function (Blueprint $table) {
            $table->unsignedTinyInteger('ride_type')->default(1)->after('vehicle_type')->comment('1: City, 2: Intercity');
            $table->date('travel_date')->nullable()->after('ride_type');
            $table->time('travel_time')->nullable()->after('travel_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn(['ride_type', 'travel_date', 'travel_time']);
        });
    }
};
