<?php

declare(strict_types=1);

namespace App\Modules\Driver\Model\Enums;

enum FileDisk: int
{
    case PUBLIC  = 1;
    case PRIVATE = 2;

    public function getDiskName(): string
    {
        return match ($this) {
            self::PUBLIC  => 'public',
            self::PRIVATE => 'local',
        };
    }
}
