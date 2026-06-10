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
        if (Schema::hasTable('vehicle_types')) {
            Schema::table('vehicle_types', function (Blueprint $table) {
                if (!Schema::hasColumn('vehicle_types', 'service_scopes')) {
                    $table->json('service_scopes')->nullable()->after('estimated_wait_time');
                }

                if (!Schema::hasColumn('vehicle_types', 'is_bookable')) {
                    $table->boolean('is_bookable')->default(true)->after('service_scopes');
                }
            });
        }

        if (Schema::hasTable('pricing_configs')) {
            Schema::table('pricing_configs', function (Blueprint $table) {
                if (!Schema::hasColumn('pricing_configs', 'vehicle_type_id')) {
                    $table->unsignedInteger('vehicle_type_id')->nullable()->after('id');
                }

                if (!Schema::hasColumn('pricing_configs', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('commission_rate');
                }
            });

            DB::table('pricing_configs')->whereNull('vehicle_type_id')->update([
                'vehicle_type_id' => DB::raw('vehicle_type'),
            ]);

            Schema::table('pricing_configs', function (Blueprint $table) {
                $table->unique(['vehicle_type_id', 'is_active'], 'pricing_configs_vehicle_type_active_unique');
            });
        }

        if (Schema::hasTable('pricing_config_history')) {
            Schema::table('pricing_config_history', function (Blueprint $table) {
                if (!Schema::hasColumn('pricing_config_history', 'vehicle_type_id')) {
                    $table->unsignedInteger('vehicle_type_id')->nullable()->after('id');
                }
            });

            DB::table('pricing_config_history')->whereNull('vehicle_type_id')->update([
                'vehicle_type_id' => DB::raw('vehicle_type'),
            ]);
        }

        if (Schema::hasTable('pricing_surge_rules')) {
            Schema::table('pricing_surge_rules', function (Blueprint $table) {
                if (!Schema::hasColumn('pricing_surge_rules', 'vehicle_type_id')) {
                    $table->unsignedInteger('vehicle_type_id')->nullable()->after('id');
                }
            });

            DB::table('pricing_surge_rules')->whereNull('vehicle_type_id')->update([
                'vehicle_type_id' => DB::raw('vehicle_type'),
            ]);
        }

        if (Schema::hasTable('scheduled_pricing_rules')) {
            Schema::table('scheduled_pricing_rules', function (Blueprint $table) {
                if (!Schema::hasColumn('scheduled_pricing_rules', 'vehicle_type_id')) {
                    $table->unsignedInteger('vehicle_type_id')->nullable()->after('ride_mode');
                }
            });

            DB::table('scheduled_pricing_rules')->whereNull('vehicle_type_id')->update([
                'vehicle_type_id' => DB::raw('vehicle_type'),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('scheduled_pricing_rules')) {
            Schema::table('scheduled_pricing_rules', function (Blueprint $table) {
                if (Schema::hasColumn('scheduled_pricing_rules', 'vehicle_type_id')) {
                    $table->dropColumn('vehicle_type_id');
                }
            });
        }

        if (Schema::hasTable('pricing_surge_rules')) {
            Schema::table('pricing_surge_rules', function (Blueprint $table) {
                if (Schema::hasColumn('pricing_surge_rules', 'vehicle_type_id')) {
                    $table->dropColumn('vehicle_type_id');
                }
            });
        }

        if (Schema::hasTable('pricing_config_history')) {
            Schema::table('pricing_config_history', function (Blueprint $table) {
                if (Schema::hasColumn('pricing_config_history', 'vehicle_type_id')) {
                    $table->dropColumn('vehicle_type_id');
                }
            });
        }

        if (Schema::hasTable('pricing_configs')) {
            Schema::table('pricing_configs', function (Blueprint $table) {
                if (Schema::hasColumn('pricing_configs', 'vehicle_type_id')) {
                    $table->dropUnique('pricing_configs_vehicle_type_active_unique');
                    $table->dropColumn('vehicle_type_id');
                }

                if (Schema::hasColumn('pricing_configs', 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }

        if (Schema::hasTable('vehicle_types')) {
            Schema::table('vehicle_types', function (Blueprint $table) {
                if (Schema::hasColumn('vehicle_types', 'is_bookable')) {
                    $table->dropColumn('is_bookable');
                }

                if (Schema::hasColumn('vehicle_types', 'service_scopes')) {
                    $table->dropColumn('service_scopes');
                }
            });
        }
    }
};
