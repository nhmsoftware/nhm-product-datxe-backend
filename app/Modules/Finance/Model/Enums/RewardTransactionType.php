<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model\Enums;

enum RewardTransactionType: int
{
    case EARN = 1;
    case REDEEM = 2;
    case EXPIRE = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::EARN => 'Tích điểm',
            self::REDEEM => 'Sử dụng điểm',
            self::EXPIRE => 'Điểm hết hạn',
        };
    }
}
