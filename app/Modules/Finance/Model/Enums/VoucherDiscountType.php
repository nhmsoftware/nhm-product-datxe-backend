<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model\Enums;

/**
 * Loại giảm giá của voucher.
 */
enum VoucherDiscountType: int
{
    case FIXED = 1;   // Giảm số tiền cố định
    case PERCENT = 2; // Giảm theo phần trăm

    public function getLabel(): string
    {
        return match ($this) {
            self::FIXED => 'Giảm tiền mặt',
            self::PERCENT => 'Giảm phần trăm',
        };
    }
}
