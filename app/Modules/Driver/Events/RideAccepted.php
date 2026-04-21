<?php

declare(strict_types=1);

namespace App\Modules\Driver\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RideAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $rideId,
        public readonly string $driverId
    ) {}
}
