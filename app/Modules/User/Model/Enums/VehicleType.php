<?php

declare(strict_types=1);

namespace App\Modules\User\Model\Enums;

use App\Core\Traits\EnumHelper;

enum VehicleType: int
{
    use EnumHelper;

    case Bike = 1;
    case Car4 = 2;
    case Car7 = 3;

    public function label(): string
    {
        return match($this) {
            self::Bike => 'Xe máy',
            self::Car4 => 'Ô tô 4 chỗ',
            self::Car7 => 'Ô tô 7 chỗ',
        };
    }
}
