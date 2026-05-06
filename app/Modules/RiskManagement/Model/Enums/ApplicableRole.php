<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model\Enums;

enum ApplicableRole: int
{
    case DRIVER   = 1;
    case CUSTOMER = 2;
    case MERCHANT = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::DRIVER   => 'Tài xế',
            self::CUSTOMER => 'Khách hàng',
            self::MERCHANT => 'Cửa hàng/Merchant',
        };
    }
}
