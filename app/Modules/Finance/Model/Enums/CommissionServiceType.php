<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model\Enums;

/**
 * Loại dịch vụ áp dụng hoa hồng.
 */
enum CommissionServiceType: int
{
    case RIDE     = 1;
    case FOOD      = 2;
    case DELIVERY  = 3;
    case INTERCITY = 6;
    case AIRPORT   = 7;

    public function getLabel(): string
    {
        return match ($this) {
            self::RIDE     => 'Chuyến xe',
            self::FOOD      => 'Đồ ăn',
            self::DELIVERY  => 'Giao hàng',
            self::INTERCITY => 'Đi tỉnh',
            self::AIRPORT   => 'Sân bay',
        };
    }
}
