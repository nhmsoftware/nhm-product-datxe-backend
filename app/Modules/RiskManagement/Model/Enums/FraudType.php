<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model\Enums;

/**
 * Loại hành vi gian lận.
 */
enum FraudType: int
{
    case FAKE_GPS            = 1;
    case PROMO_ABUSE         = 2;
    case GHOST_RIDE          = 3;
    case UNUSUAL_TRANSACTION = 4;
    case ACCOUNT_TAKEOVER    = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::FAKE_GPS            => 'Giả lập tọa độ GPS',
            self::PROMO_ABUSE         => 'Lạm dụng khuyến mãi',
            self::GHOST_RIDE          => 'Chuyến xe ma',
            self::UNUSUAL_TRANSACTION => 'Giao dịch bất thường',
            self::ACCOUNT_TAKEOVER    => 'Chiếm đoạt tài khoản',
        };
    }
}
