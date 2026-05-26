<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix Bug: Đơn đồ ăn bị hiển thị 2 lần trong danh sách lịch sử khách hàng.
 *
 * Root cause: customer_orders_view cũ lấy TẤT CẢ rows từ bảng `rides` (kể cả
 * những ride có ride_type = DELIVERY = 4 — là các ride được tạo ra để vận chuyển
 * đơn đồ ăn), VÀ ĐỒNG THỜI lấy TẤT CẢ rows từ bảng `food_orders`.
 *
 * Kết quả: 1 đơn đồ ăn xuất hiện 2 lần:
 *   - 1 lần service_type='food'  (từ food_orders)
 *   - 1 lần service_type='ride'  (từ rides DELIVERY được gán bởi Admin)
 *
 * Fix: Thêm điều kiện `AND ride_type != 4` vào phần SELECT từ bảng `rides`
 * để loại trừ các rides chỉ là "phương tiện giao hàng" của food_orders.
 *
 * ride_type values: 1=CITY, 2=INTERCITY, 3=AIRPORT, 4=DELIVERY, 5=CHAUFFEUR
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite không hỗ trợ CREATE OR REPLACE VIEW — phải DROP trước
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

    public function down(): void
    {
        // Khôi phục về View cũ (có lỗi) — chỉ dùng khi rollback khẩn cấp
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("DROP VIEW IF EXISTS customer_orders_view;");
            DB::statement("
                CREATE VIEW customer_orders_view AS
                SELECT id, customer_id, 'ride' as service_type, total_price, status, pickup_address, destination_address, created_at
                FROM rides WHERE deleted_at IS NULL
                UNION ALL
                SELECT id, customer_id, 'food' as service_type, total_price, status, NULL as pickup_address, delivery_address as destination_address, created_at
                FROM food_orders WHERE deleted_at IS NULL;
            ");
            return;
        }

        DB::statement("
            CREATE OR REPLACE VIEW customer_orders_view AS
            SELECT
                id::text as id, customer_id::text as customer_id, 'ride' as service_type,
                total_price, status, pickup_address, destination_address, created_at
            FROM rides WHERE deleted_at IS NULL
            UNION ALL
            SELECT
                id::text as id, customer_id::text as customer_id, 'food' as service_type,
                total_price, status, NULL as pickup_address, delivery_address as destination_address, created_at
            FROM food_orders WHERE deleted_at IS NULL;
        ");
    }
};
