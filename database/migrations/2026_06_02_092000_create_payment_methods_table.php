<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * UC-45: Tạo bảng cấu hình phương thức nạp tiền do Admin quản lý.
     * Loại: e_wallet (momo, zalopay, vnpay), bank_card, bank_transfer
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('type');                          // e_wallet | bank_card | bank_transfer
            $table->string('code')->unique();                // momo | zalopay | vnpay | bank_card | bank_transfer
            $table->string('name');                          // Tên hiển thị: "Ví MoMo", "Thẻ ngân hàng nội địa"...
            $table->boolean('is_active')->default(false);    // Admin bật/tắt
            $table->decimal('min_amount', 15, 2)->default(10000);   // Số tiền nạp tối thiểu
            $table->decimal('max_amount', 15, 2)->default(10000000); // Số tiền nạp tối đa
            $table->json('transfer_info')->nullable();       // Cho bank_transfer: {bank_name, account_number, account_name, bank_code}
            $table->string('icon_url')->nullable();          // URL icon phương thức
            $table->json('metadata')->nullable();            // Thông tin thêm
            $table->integer('sort_order')->default(0);       // Thứ tự hiển thị
            $table->unsignedBigInteger('updated_by')->nullable(); // Admin cập nhật cuối
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
