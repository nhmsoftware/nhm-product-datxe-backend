<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model\Enums;

/**
 * Loại đối tượng bị cảnh báo gian lận.
 */
enum FraudTargetType: int
{
    case CUSTOMER    = 1;
    case DRIVER      = 2;
    case MERCHANT    = 3;
    case TRANSACTION = 4;

    public function getLabel(): string
    {
        return match ($this) {
            self::CUSTOMER    => 'Khách hàng',
            self::DRIVER      => 'Tài xế',
            self::MERCHANT    => 'Đối tác',
            self::TRANSACTION => 'Giao dịch',
        };
    }
}
