<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Finance\Model\Enums\VoucherDiscountType;
use App\Modules\Finance\Model\Enums\VoucherServiceType;
use App\Modules\Finance\Model\Voucher;
use Illuminate\Database\Seeder;

final class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vouchers = [
            [
                'code' => 'RIDE10K',
                'service_type' => VoucherServiceType::RIDE,
                'discount_type' => VoucherDiscountType::FIXED,
                'discount_value' => 10000,
                'min_order_amount' => 50000,
                'valid_from' => now()->subDay(),
                'valid_until' => now()->addMonth(),
                'total_usage_limit' => 1000,
                'description' => 'Giảm 10,000đ cho chuyến xe từ 50,000đ.',
            ],
            [
                'code' => 'FOOD50',
                'service_type' => VoucherServiceType::FOOD,
                'discount_type' => VoucherDiscountType::PERCENT,
                'discount_value' => 10, // 10%
                'min_order_amount' => 100000,
                'max_discount_amount' => 50000,
                'valid_from' => now()->subDay(),
                'valid_until' => now()->addMonth(),
                'total_usage_limit' => 500,
                'description' => 'Giảm 10% tối đa 50,000đ cho đơn hàng đồ ăn từ 100,000đ.',
            ],
            [
                'code' => 'XINCHAO',
                'service_type' => VoucherServiceType::BOTH,
                'discount_type' => VoucherDiscountType::FIXED,
                'discount_value' => 20000,
                'min_order_amount' => 0,
                'valid_from' => now()->subDay(),
                'valid_until' => now()->addWeek(),
                'total_usage_limit' => 2000,
                'description' => 'Mã chào mừng: Giảm 20,000đ cho mọi dịch vụ.',
            ],
            [
                'code' => 'EXPIRED',
                'service_type' => VoucherServiceType::BOTH,
                'discount_type' => VoucherDiscountType::FIXED,
                'discount_value' => 50000,
                'min_order_amount' => 0,
                'valid_from' => now()->subMonth(),
                'valid_until' => now()->subDay(),
                'total_usage_limit' => 100,
                'description' => 'Mã đã hết hạn dùng để test.',
            ],
        ];

        foreach ($vouchers as $voucher) {
            Voucher::updateOrCreate(['code' => $voucher['code']], $voucher);
        }
    }
}
