<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Events;

final class FreeModeToggled
{
    public function __construct(
        public readonly bool $isFreeMode,
    ) {}
}
