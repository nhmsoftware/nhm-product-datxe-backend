<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Model\Enums;

enum MarketingItemStatus: int
{
    case ACTIVE = 1;
    case INACTIVE = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Hoạt động',
            self::INACTIVE => 'Đang ẩn',
        };
    }
}
