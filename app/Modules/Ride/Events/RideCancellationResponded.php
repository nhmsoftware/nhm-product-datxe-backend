<?php

declare(strict_types=1);

namespace App\Modules\Ride\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RideCancellationResponded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $rideId,
        public readonly string $customerId,
        public readonly string $driverId,
        public readonly bool $isApproved,
    ) {}
}
