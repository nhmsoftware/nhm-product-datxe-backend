<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cập nhật view để không lọc nhầm các đơn Giao hàng (DELIVERY = 4).
 *
 * Trước đây, hệ thống dùng chung ride_type=4 cho cả Food Delivery và Goods Delivery.
 * Migration cũ đã dùng AND ride_type != 4 để không hiển thị lặp các đơn đồ ăn,
 * dẫn đến vô tình ẩn đi cả các đơn Giao hàng.
 *
 * Hiện tại đã tách Food Delivery thành ride_type=6.
 * Migration này thay đổi điều kiện thành AND ride_type != 6.
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

        // PostgreSQL
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

    public function down(): void
    {
        // Khôi phục lại trạng thái cũ nếu rollback
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
                  AND ride_type != 4
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
              AND ride_type != 4
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
