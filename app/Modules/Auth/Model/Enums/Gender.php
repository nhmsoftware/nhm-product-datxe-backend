<?php

declare(strict_types=1);

namespace App\Modules\Auth\Model\Enums;

use App\Core\Traits\EnumHelper;

enum Gender: int
{
    use EnumHelper;
    case Male   = 1;
    case Female = 2;
    case Other  = 3;

    public function label(): string
    {
        return match($this) {
            self::Male   => 'Nam',
            self::Female => 'Nữ',
            self::Other  => 'Khác',
        };
    }
}
