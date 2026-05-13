<?php

declare(strict_types=1);

namespace App\Modules\User\Model\Enums;

enum DriverStatus: int
{
    case ACTIVE   = 1;
    case COOLDOWN = 2;
    case BANNED   = 3;
    case BUSY     = 4;
    case DISPATCH_LOCKED = 5; // Khóa nhận cuốc (ví thấp)

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE   => 'Đang hoạt động',
            self::COOLDOWN => 'Đang bị đóng băng',
            self::BANNED   => 'Bị khóa',
            self::BUSY     => 'Đang bận',
            self::DISPATCH_LOCKED => 'Khóa nhận cuốc',
        };
    }
}
