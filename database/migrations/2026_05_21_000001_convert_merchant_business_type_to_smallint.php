<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (!Schema::hasColumn('merchant_profiles', 'business_type')) {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE merchant_profiles
            ALTER COLUMN business_type TYPE SMALLINT
            USING CASE
                WHEN business_type IS NULL OR business_type = '' THEN NULL
                WHEN business_type ~ '^[0-9]+$' THEN business_type::SMALLINT
                WHEN business_type IN ('restaurant', 'Ăn uống') OR business_type ILIKE '%nhà hàng%' THEN 1
                WHEN business_type IN ('cafe', 'coffee') OR business_type ILIKE '%cà phê%' THEN 2
                WHEN business_type = 'milk_tea' OR business_type ILIKE '%trà sữa%' THEN 3
                WHEN business_type = 'fast_food' OR business_type ILIKE '%đồ ăn nhanh%' THEN 4
                WHEN business_type = 'street_food' OR business_type ILIKE '%đường phố%' THEN 5
                WHEN business_type = 'bakery' OR business_type ILIKE '%bánh%' THEN 6
                WHEN business_type = 'grocery' OR business_type ILIKE '%tạp hóa%' THEN 7
                ELSE 8
            END
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (!Schema::hasColumn('merchant_profiles', 'business_type')) {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE merchant_profiles
            ALTER COLUMN business_type TYPE VARCHAR(100)
            USING business_type::VARCHAR
        SQL);
    }
};
