<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Events;

final class PricingConfigUpdated
{
    public function __construct(
        public readonly int   $vehicleType,
        public readonly array $oldConfig,
        public readonly array $newConfig,
    ) {}
}
