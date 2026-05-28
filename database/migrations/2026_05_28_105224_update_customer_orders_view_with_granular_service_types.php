<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cập nhật view để trả về service_type cụ thể cho các chuyến xe
 * thay vì gom chung thành 'ride'.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("DROP VIEW IF EXISTS customer_orders_view;");
            DB::statement("
                CREATE VIEW customer_orders_view AS
                SELECT
                    id,
                    customer_id,
                    CASE ride_type
                        WHEN 1 THEN 'ride'
                        WHEN 2 THEN 'intercity'
                        WHEN 3 THEN 'airport'
                        WHEN 4 THEN 'delivery'
                        WHEN 5 THEN 'chauffeur'
                        ELSE 'ride'
                    END as service_type,
                    total_price,
                    status,
                    pickup_address,
                    destination_address,
                    created_at
                FROM rides
                WHERE deleted_at IS NULL
                  AND ride_type != 6
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

        // PostgreSQL
        DB::statement("
            CREATE OR REPLACE VIEW customer_orders_view AS
            SELECT
                id::text          AS id,
                customer_id::text AS customer_id,
                CASE ride_type
                    WHEN 1 THEN 'ride'
                    WHEN 2 THEN 'intercity'
                    WHEN 3 THEN 'airport'
                    WHEN 4 THEN 'delivery'
                    WHEN 5 THEN 'chauffeur'
                    ELSE 'ride'
                END               AS service_type,
                total_price       AS total_price,
                status            AS status,
                pickup_address    AS pickup_address,
                destination_address AS destination_address,
                created_at        AS created_at
            FROM rides
            WHERE deleted_at IS NULL
              AND ride_type != 6
            UNION ALL
            SELECT
                id::text             AS id,
                customer_id::text    AS customer_id,
                'food'               AS service_type,
                total_price          AS total_price,
                status               AS status,
                NULL                 AS pickup_address,
                delivery_address     AS destination_address,
                created_at           AS created_at
            FROM food_orders
            WHERE deleted_at IS NULL;
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("DROP VIEW IF EXISTS customer_orders_view;");
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
                  AND ride_type != 6
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
                id::text          AS id,
                customer_id::text AS customer_id,
                'ride'            AS service_type,
                total_price       AS total_price,
                status            AS status,
                pickup_address    AS pickup_address,
                destination_address AS destination_address,
                created_at        AS created_at
            FROM rides
            WHERE deleted_at IS NULL
              AND ride_type != 6
            UNION ALL
            SELECT
                id::text             AS id,
                customer_id::text    AS customer_id,
                'food'               AS service_type,
                total_price          AS total_price,
                status               AS status,
                NULL                 AS pickup_address,
                delivery_address     AS destination_address,
                created_at           AS created_at
            FROM food_orders
            WHERE deleted_at IS NULL;
        ");
    }
};
