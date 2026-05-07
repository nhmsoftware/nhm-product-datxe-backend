<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Model\Enums;

enum ScheduledDispatchMode: int
{
    case INTERNAL_PRIORITY = 1; // Mode 1 – Internal Fleet Priority
    case OPEN_POOL         = 2; // Mode 2 – Open Driver Pool

    public function getLabel(): string
    {
        return match ($this) {
            self::INTERNAL_PRIORITY => 'Internal Fleet Priority',
            self::OPEN_POOL         => 'Open Driver Pool',
        };
    }
}
