<?php

declare(strict_types=1);

namespace App\Modules\User\Model\Enums;

use App\Core\Traits\EnumHelper;

enum DriverGroupType: int
{
    use EnumHelper;

    case INTERNAL = 1; // Đội xe nhà
    case PARTNER  = 2; // Tài xế đối tác

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Xe nhà',
            self::PARTNER  => 'Xe khách',
        };
    }
}
