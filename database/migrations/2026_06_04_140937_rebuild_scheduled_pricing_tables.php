<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old table
        Schema::dropIfExists('scheduled_pricing_configs');

        // 1. Phụ phí & Cấu hình chung (Global Surcharges)
        Schema::create('scheduled_pricing_surcharges', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->decimal('pre_book_surcharge', 15, 2)->default(0)->comment('Phụ phí đặt trước');
            $table->decimal('night_surcharge', 15, 2)->default(0)->comment('Phụ phí ban đêm');
            $table->decimal('holiday_surcharge', 15, 2)->default(0)->comment('Phụ phí ngày lễ');
            $table->decimal('waiting_surcharge', 15, 2)->default(0)->comment('Phụ phí chờ');
            $table->decimal('toll_surcharge', 15, 2)->default(0)->comment('Phụ phí cầu đường cố định');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Rules cấu hình giá (Intercity / Airport)
        Schema::create('scheduled_pricing_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedTinyInteger('service_type')->comment('Enum: 6=Intercity, 7=Airport');
            $table->string('ride_mode')->comment('shared, private, to_airport, from_airport');
            $table->unsignedTinyInteger('vehicle_type')->comment('Enum VehicleType (1=Bike, 2=Car4, 3=Car7, ...)');
            $table->string('airport_id')->nullable()->comment('ID sân bay nếu service là Airport');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ràng buộc duy nhất: Chỉ có 1 rule cho 1 cụm cấu hình đang hoạt động (có thể handle bằng repository cũng được)
        });

        // 3. Các khoảng KM giá cho từng Rule
        Schema::create('scheduled_pricing_ranges', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('scheduled_pricing_rule_id')->constrained()->cascadeOnDelete();
            $table->decimal('start_km', 8, 2);
            $table->decimal('end_km', 8, 2);
            $table->decimal('price', 15, 2);
            $table->string('unit', 50)->default('per_trip')->comment('per_trip (Bao xe), per_passenger (Ghép)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_pricing_ranges');
        Schema::dropIfExists('scheduled_pricing_rules');
        Schema::dropIfExists('scheduled_pricing_surcharges');

        // Phục hồi lại bảng cũ (nếu cần downgrade)
        Schema::create('scheduled_pricing_configs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->decimal('base_price', 15, 2)->default(0);
            $table->decimal('scheduled_surcharge', 15, 2)->default(0);
            $table->decimal('intercity_base_price', 15, 2)->default(0);
            $table->decimal('intercity_distance_rate', 15, 2)->default(0);
            $table->decimal('intercity_time_rate', 15, 2)->default(0);
            $table->decimal('intercity_min_fare', 15, 2)->default(0);
            $table->decimal('airport_base_price', 15, 2)->default(0);
            $table->decimal('airport_distance_rate', 15, 2)->default(0);
            $table->decimal('airport_time_rate', 15, 2)->default(0);
            $table->decimal('airport_min_fare', 15, 2)->default(0);
            $table->decimal('delivery_base_price', 15, 2)->default(0);
            $table->decimal('delivery_distance_rate', 15, 2)->default(0);
            $table->decimal('delivery_time_rate', 15, 2)->default(0);
            $table->decimal('delivery_min_fare', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
