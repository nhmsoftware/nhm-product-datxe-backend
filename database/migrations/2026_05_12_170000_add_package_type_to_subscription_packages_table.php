<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UC-118: Thêm cột package_type vào bảng subscription_packages
 * Loại gói: daily (ngày), weekly (tuần), monthly (tháng)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            // Thêm sau cột name
            $table->string('package_type')->default('monthly')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn('package_type');
        });
    }
};
