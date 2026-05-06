<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model\Enums;

enum PenaltyType: int
{
    case WARNING          = 1;
    case TEMPORARY_BAN    = 2;
    case PERMANENT_BAN    = 3;
    case MONETARY_PENALTY = 4;
    case REPUTATION_DEDUCTION = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::WARNING            => 'Cảnh báo',
            self::TEMPORARY_BAN      => 'Khóa tài khoản tạm thời',
            self::PERMANENT_BAN      => 'Khóa tài khoản vĩnh viễn',
            self::MONETARY_PENALTY   => 'Phạt tiền',
            self::REPUTATION_DEDUCTION => 'Giảm điểm uy tín',
        };
    }
}
