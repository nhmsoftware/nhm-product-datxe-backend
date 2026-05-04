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
            $table->unsignedBigInteger('airport_id')->nullable()->after('travel_time');
            $table->unsignedTinyInteger('airport_direction')->nullable()->after('airport_id')->comment('1: To Airport, 2: From Airport');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn(['airport_id', 'airport_direction']);
        });
    }
};
