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
        Schema::table('rides', function (Blueprint $blueprint) {
            // Thêm cột time_fare với kiểu decimal (15,2) để lưu tiền tệ chính xác
            // Đặt sau distance_price để dễ quản lý dữ liệu
            $blueprint->decimal('time_fare', 15, 2)
                ->default(0)
                ->after('distance_price')
                ->comment('Phí tính theo thời gian di chuyển (đã gộp vào tổng nhưng lưu riêng để đối soát)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rides', function (Blueprint $blueprint) {
            $blueprint->dropColumn('time_fare');
        });
    }
};
