<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("
                CREATE VIEW customer_orders_view AS
                SELECT 
                    id,
                    customer_id,
                    'ride' as service_type,
                    total_price,
                    status,
                    pickup_address,
                    destination_address,
                    created_at
                FROM rides
                WHERE deleted_at IS NULL
                UNION ALL
                SELECT 
                    id,
                    customer_id,
                    'food' as service_type,
                    total_price,
                    status,
                    NULL as pickup_address,
                    delivery_address as destination_address,
                    created_at
                FROM food_orders
                WHERE deleted_at IS NULL;
            ");
            return;
        }

        DB::statement("
            CREATE OR REPLACE VIEW customer_orders_view AS
            SELECT 
                id::text as id,
                customer_id::text as customer_id,
                'ride' as service_type,
                total_price as total_price,
                status as status,
                pickup_address as pickup_address,
                destination_address as destination_address,
                created_at as created_at
            FROM rides
            WHERE deleted_at IS NULL
            UNION ALL
            SELECT 
                id::text as id,
                customer_id::text as customer_id,
                'food' as service_type,
                total_price as total_price,
                status as status,
                NULL as pickup_address,
                delivery_address as destination_address,
                created_at as created_at
            FROM food_orders
            WHERE deleted_at IS NULL;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS customer_orders_view;");
    }
};
