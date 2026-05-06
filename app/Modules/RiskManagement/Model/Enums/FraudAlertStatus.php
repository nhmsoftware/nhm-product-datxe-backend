<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model\Enums;

/**
 * Trạng thái xử lý cảnh báo gian lận.
 */
enum FraudAlertStatus: int
{
    case PENDING       = 1;
    case INVESTIGATING = 2;
    case RESOLVED      = 3;
    case DISMISSED     = 4;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING       => 'Chờ xử lý',
            self::INVESTIGATING => 'Đang điều tra',
            self::RESOLVED      => 'Đã giải quyết',
            self::DISMISSED     => 'Bị bác bỏ',
        };
    }
}
