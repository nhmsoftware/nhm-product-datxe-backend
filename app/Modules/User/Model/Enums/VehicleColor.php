<?php

declare(strict_types=1);

namespace App\Modules\User\Model\Enums;

use App\Core\Traits\EnumHelper;

enum VehicleColor: int
{
    use EnumHelper;

    case White  = 1;
    case Black  = 2;
    case Silver = 3;
    case Red    = 4;
    case Blue   = 5;

    public function label(): string
    {
        return match($this) {
            self::White  => 'Trắng',
            self::Black  => 'Đen',
            self::Silver => 'Bạc',
            self::Red    => 'Đỏ',
            self::Blue   => 'Xanh',
        };
    }
}
