<?php

declare(strict_types=1);

namespace App\Modules\Driver\Listeners;

use App\Modules\Driver\Events\RideStarted;
use Illuminate\Support\Facades\Redis;

final class NotifyRealtimeOnRideStarted
{
    /**
     * Handle the event.
     */
    public function handle(RideStarted $event): void
    {
        Redis::publish('ride.communication.events', json_encode([
            'event'    => 'ride.started',
            'ride_id'  => $event->rideId,
            'driverId' => $event->driverId,
        ]));
    }
}
