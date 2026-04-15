<?php

declare(strict_types=1);

namespace App\Modules\Driver\Model\Enums;

enum KycType: int
{
    case DRIVER         = 1;
    case MERCHANTS      = 2;
    case CHANGE_VEHICLE = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::DRIVER         => 'Đăng ký tài xế',
            self::MERCHANTS      => 'Đăng ký quán ăn',
            self::CHANGE_VEHICLE => 'Thay đổi phương tiện',
        };
    }
}
