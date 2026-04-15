<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model\Enums;

enum RideChatMessageStatus: int
{
    case SENT = 1;
    case DELIVERED = 2;
    case FAILED = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::SENT => 'Đã gửi',
            self::DELIVERED => 'Đã nhận',
            self::FAILED => 'Gửi thất bại',
        };
    }
}
