<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm các trường liên quan đến voucher vào bảng rides.
 * Tách biệt voucher_code (string) và discount_amount để có thể
 * lưu mã giảm giá và số tiền giảm trực tiếp mà không cần JOIN qua voucher_id.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            // Lưu mã voucher được áp dụng (UC-11)
            $table->string('voucher_code', 50)->nullable()->after('voucher_id');
            // Số tiền giảm giá sau khi áp dụng voucher
            $table->decimal('discount_amount', 15, 2)->default(0)->after('voucher_code');
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn(['voucher_code', 'discount_amount']);
        });
    }
};
