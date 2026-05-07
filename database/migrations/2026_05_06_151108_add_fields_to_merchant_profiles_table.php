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
            if (!Schema::hasColumn('merchant_profiles', 'business_type')) {
                $table->string('business_type', 100)->nullable()->after('store_address');
            }
            if (!Schema::hasColumn('merchant_profiles', 'citizen_id_image')) {
                $table->string('citizen_id_image')->nullable()->after('business_license_image');
            }
            if (!Schema::hasColumn('merchant_profiles', 'store_image')) {
                $table->string('store_image')->nullable()->after('citizen_id_image');
            }
            if (!Schema::hasColumn('merchant_profiles', 'status')) {
                $table->tinyInteger('status')->default(1)->comment('1: Pending, 2: Approved, 3: Rejected')->after('total_orders');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            $table->dropColumn(['business_type', 'citizen_id_image', 'store_image', 'status']);
        });
    }
};
