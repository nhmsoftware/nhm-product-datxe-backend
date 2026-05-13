<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model\Enums;

use App\Core\Traits\EnumHelper;

/**
 * Đối tượng áp dụng hoa hồng.
 */
enum CommissionTargetType: int
{
    use EnumHelper;

    case DRIVER   = 1; // Tài xế
    case MERCHANT = 2; // Merchant/Nhà hàng

    public function label(): string
    {
        return match ($this) {
            self::DRIVER   => 'Tài xế',
            self::MERCHANT => 'Merchant/Nhà hàng',
        };
    }
}
