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
            if (Schema::hasColumn('merchant_profiles', 'discount_percent')) {
                $table->renameColumn('discount_percent', 'commission_rate');
            }
            if (!Schema::hasColumn('merchant_profiles', 'commission_package')) {
                $table->string('commission_package', 50)->default('BASIC')->after('commission_rate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_profiles', 'commission_rate')) {
                $table->renameColumn('commission_rate', 'discount_percent');
            }
            $table->dropColumn('commission_package');
        });
    }
};
