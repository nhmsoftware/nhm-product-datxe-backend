<?php

declare(strict_types=1);

namespace App\Modules\Ride\Listeners;

use App\Modules\Ride\Events\RideCanceled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

final class NotifyRealtimeOnRideCanceled implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(RideCanceled $event): void
    {
        try {
            $payload = [
                'event'       => 'ride.cancelled',
                'ride_id'     => (string) $event->rideId,
                'customer_id' => (string) $event->customerId,
                'driver_id'   => $event->driverId ? (string) $event->driverId : null,
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish to the communication channel expected by the Node.js realtime service
            Redis::publish('ride.communication.events', json_encode($payload));

            Log::info('Realtime notification sent: ride.cancelled', [
                'ride_id' => $event->rideId,
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnRideCanceled failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId
            ]);
        }
    }
}
