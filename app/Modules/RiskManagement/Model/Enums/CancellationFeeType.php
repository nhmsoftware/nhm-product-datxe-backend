<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model\Enums;

enum CancellationFeeType: int
{
    case FIXED      = 1;
    case PERCENTAGE = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::FIXED      => 'Số tiền cố định',
            self::PERCENTAGE => 'Phần trăm giá trị chuyến đi',
        };
    }
}
