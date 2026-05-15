<?php

declare(strict_types=1);

namespace App\Modules\Notification\Model\Enums;

enum NotificationCategory: string
{
    case Promotion = 'promotion';
    case Order = 'order';
    case System = 'system';

    public function getLabel(): string
    {
        return match ($this) {
            self::Promotion => 'Khuyến mãi',
            self::Order => 'Đơn hàng',
            self::System => 'Hệ thống',
        };
    }
}
