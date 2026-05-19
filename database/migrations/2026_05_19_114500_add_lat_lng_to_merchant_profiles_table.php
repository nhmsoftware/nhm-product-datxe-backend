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
        Schema::table('merchant_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_profiles', 'lat') && !Schema::hasColumn('merchant_profiles', 'latitude')) {
                $table->renameColumn('lat', 'latitude');
            } elseif (!Schema::hasColumn('merchant_profiles', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('store_address');
            }

            if (Schema::hasColumn('merchant_profiles', 'lng') && !Schema::hasColumn('merchant_profiles', 'longitude')) {
                $table->renameColumn('lng', 'longitude');
            } elseif (!Schema::hasColumn('merchant_profiles', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_profiles', 'latitude') && !Schema::hasColumn('merchant_profiles', 'lat')) {
                $table->renameColumn('latitude', 'lat');
            }
            if (Schema::hasColumn('merchant_profiles', 'longitude') && !Schema::hasColumn('merchant_profiles', 'lng')) {
                $table->renameColumn('longitude', 'lng');
            }
        });
    }
};
