<?php

declare(strict_types=1);

namespace App\Modules\User\Model\Enums;

use App\Core\Traits\EnumHelper;

enum KycType: int
{
    use EnumHelper;

    case Driver         = 1;
    case Merchants      = 2;
    case Change_Vehicle = 3;

    public function label(): string
    {
        return match($this) {
            self::Driver         => 'Tài xế',
            self::Merchants      => 'Quán ăn',
            self::Change_Vehicle => 'Thay đổi xe',
        };
    }
}
