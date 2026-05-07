<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Events;

use App\Modules\Pricing\Model\Enums\ScheduledDispatchMode;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ScheduledRideDispatchModeChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ScheduledDispatchMode $newMode,
        public readonly string $occurredAt
    ) {}
}
