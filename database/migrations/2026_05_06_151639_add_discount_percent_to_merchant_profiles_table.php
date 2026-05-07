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
            if (!Schema::hasColumn('merchant_profiles', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->default(0)->after('is_open');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });
    }
};
