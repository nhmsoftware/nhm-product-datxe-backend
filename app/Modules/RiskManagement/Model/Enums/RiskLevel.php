<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model\Enums;

/**
 * Mức độ rủi ro của cảnh báo gian lận.
 */
enum RiskLevel: int
{
    case LOW      = 1;
    case MEDIUM   = 2;
    case HIGH     = 3;
    case CRITICAL = 4;

    public function getLabel(): string
    {
        return match ($this) {
            self::LOW      => 'Thấp',
            self::MEDIUM   => 'Trung bình',
            self::HIGH     => 'Cao',
            self::CRITICAL => 'Nghiêm trọng',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::LOW      => '#10b981', // green-500
            self::MEDIUM   => '#f59e0b', // amber-500
            self::HIGH     => '#f97316', // orange-500
            self::CRITICAL => '#ef4444', // red-500
        };
    }
}
