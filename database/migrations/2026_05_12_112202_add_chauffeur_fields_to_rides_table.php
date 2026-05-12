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
            $table->string('chauffeur_license_plate')->nullable()->after('airport_direction');
            $table->string('chauffeur_vehicle_type')->nullable()->after('chauffeur_license_plate');
            $table->string('chauffeur_brand')->nullable()->after('chauffeur_vehicle_type');
            $table->string('chauffeur_color')->nullable()->after('chauffeur_brand');
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn([
                'chauffeur_license_plate',
                'chauffeur_vehicle_type',
                'chauffeur_brand',
                'chauffeur_color',
            ]);
        });
    }
};
