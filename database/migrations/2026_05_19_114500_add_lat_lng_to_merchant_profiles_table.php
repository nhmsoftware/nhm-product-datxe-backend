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
            if (!Schema::hasColumn('merchant_profiles', 'lat')) {
                $table->decimal('lat', 10, 8)->nullable()->after('store_address');
            }
            if (!Schema::hasColumn('merchant_profiles', 'lng')) {
                $table->decimal('lng', 11, 8)->nullable()->after('lat');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};
