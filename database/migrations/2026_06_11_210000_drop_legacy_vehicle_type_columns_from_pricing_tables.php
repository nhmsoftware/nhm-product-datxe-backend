<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropLegacyPricingIndexes();

        Schema::table('pricing_configs', function (Blueprint $table): void {
            if (Schema::hasColumn('pricing_configs', 'vehicle_type')) {
                $table->dropColumn('vehicle_type');
            }
        });

        Schema::table('pricing_surge_rules', function (Blueprint $table): void {
            if (Schema::hasColumn('pricing_surge_rules', 'vehicle_type')) {
                $table->dropColumn('vehicle_type');
            }
        });

        Schema::table('pricing_config_history', function (Blueprint $table): void {
            if (Schema::hasColumn('pricing_config_history', 'vehicle_type')) {
                $table->dropColumn('vehicle_type');
            }
        });

        Schema::table('scheduled_pricing_rules', function (Blueprint $table): void {
            if (Schema::hasColumn('scheduled_pricing_rules', 'vehicle_type')) {
                $table->dropColumn('vehicle_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pricing_configs', function (Blueprint $table): void {
            if (!Schema::hasColumn('pricing_configs', 'vehicle_type')) {
                $table->unsignedSmallInteger('vehicle_type')->nullable()->after('vehicle_type_id');
                $table->unique('vehicle_type', 'pricing_configs_vehicle_type_unique');
            }
        });

        Schema::table('pricing_surge_rules', function (Blueprint $table): void {
            if (!Schema::hasColumn('pricing_surge_rules', 'vehicle_type')) {
                $table->unsignedSmallInteger('vehicle_type')->nullable()->after('vehicle_type_id');
            }
        });

        Schema::table('pricing_config_history', function (Blueprint $table): void {
            if (!Schema::hasColumn('pricing_config_history', 'vehicle_type')) {
                $table->unsignedSmallInteger('vehicle_type')->nullable()->after('vehicle_type_id');
            }
        });

        Schema::table('scheduled_pricing_rules', function (Blueprint $table): void {
            if (!Schema::hasColumn('scheduled_pricing_rules', 'vehicle_type')) {
                $table->unsignedSmallInteger('vehicle_type')->nullable()->after('vehicle_type_id');
            }
        });
    }

    private function dropLegacyPricingIndexes(): void
    {
        DB::statement('DROP INDEX IF EXISTS pricing_configs_vehicle_type_unique');
    }
};
