<?php

declare(strict_types=1);

namespace App\Modules\Driver\Listeners;

use App\Modules\Driver\Events\RideCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

final class NotifyRealtimeOnRideCancelled implements ShouldQueue
{
    public function handle(RideCancelled $event): void
    {
        try {
            $payload = [
                'event'   => 'ride.cancelled',
                'ride_id' => (string) $event->rideId,
                'driver_id' => (string) $event->driverId,
                'reason'  => $event->reason,
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish to Redis channel expected by nhm-realtime service
            Redis::publish('ride.communication.events', json_encode($payload));

            Log::info('Realtime notification sent: ride.cancelled', [
                'ride_id'   => $event->rideId,
                'driver_id' => $event->driverId
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnRideCancelled failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId
            ]);
        }
    }
}
