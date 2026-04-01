<?php

declare(strict_types=1);

namespace Modules\User\Domain\Enums;

enum Gender: int
{
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
