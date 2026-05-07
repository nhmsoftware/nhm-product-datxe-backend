<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UC-25: Bảng lưu thông tin giao hàng (người gửi, người nhận, hàng hóa).
 * Mỗi delivery_order liên kết 1-1 với 1 ride (ride_type = DELIVERY).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->char('id', 26)->primary(); // ULID

            // Liên kết với chuyến xe tương ứng
            $table->unsignedBigInteger('ride_id');
            $table->foreign('ride_id')->references('id')->on('rides')->cascadeOnDelete();

            // Thông tin người gửi (Sender)
            $table->string('sender_name', 100);
            $table->string('sender_phone', 20);

            // Thông tin người nhận (Receiver)
            $table->string('receiver_name', 100);
            $table->string('receiver_phone', 20);

            // Chi tiết hàng hóa
            $table->string('goods_type', 100)->comment('Loại hàng hóa (Thực phẩm, Quần áo, Điện tử...)');
            $table->decimal('goods_weight', 8, 2)->comment('Cân nặng hàng hóa (kg)');
            $table->text('goods_note')->nullable()->comment('Ghi chú thêm về hàng hóa');
            $table->boolean('is_fragile')->default(false)->comment('Hàng dễ vỡ');

            $table->timestamps();
            $table->softDeletes();
        });

        // Thêm DELIVERY (4) vào RideType — không cần migration riêng vì là Enum PHP,
        // nhưng ta cần đảm bảo cột ride_type trong bảng rides cho phép giá trị 4.
        // Cột đã là integer nên không cần thay đổi DB.
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
