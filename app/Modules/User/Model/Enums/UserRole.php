<?php

declare(strict_types=1);

namespace App\Modules\User\Model\Enums;

use App\Core\Traits\EnumHelper;

enum UserRole: int
{
    use EnumHelper;
    case Admin     = 1;
    case Customer  = 2;
    case Driver    = 3;
    case Merchants = 4;

    public function label(): string
    {
        return match($this) {
            self::Admin     => 'Quản trị viên',
            self::Customer  => 'Khách hàng',
            self::Driver    => 'Tài xế',
            self::Merchants => 'Quán ăn',
        };
    }
}
