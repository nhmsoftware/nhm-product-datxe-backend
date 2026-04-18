<?php

declare(strict_types=1);

namespace App\Modules\Driver\Listeners;

use App\Modules\Driver\Events\RideCompleted;
use Illuminate\Support\Facades\Redis;

final class NotifyRealtimeOnRideCompleted
{
    /**
     * Handle the event.
     */
    public function handle(RideCompleted $event): void
    {
        Redis::publish('ride.communication.events', json_encode([
            'event'     => 'ride.completed',
            'ride_id'   => $event->rideId,
            'driverId'  => $event->driverId,
            'totalFare' => $event->totalFare,
        ]));
    }
}
