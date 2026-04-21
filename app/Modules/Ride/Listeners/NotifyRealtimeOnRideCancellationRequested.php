<?php

declare(strict_types=1);

namespace App\Modules\Ride\Listeners;

use App\Modules\Ride\Events\RideCancellationRequested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

final class NotifyRealtimeOnRideCancellationRequested implements ShouldQueue
{
    public function handle(RideCancellationRequested $event): void
    {
        try {
            $payload = [
                'event'       => 'ride.cancellation_requested',
                'ride_id'     => (string) $event->rideId,
                'user_id'     => (string) $event->driverId, // Send explicitly for userRoom
                'driver_id'   => (string) $event->driverId,
                'customer_id' => (string) $event->customerId,
                'reason'      => $event->reason,
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish to Redis channel expected by nhm-realtime service
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: ride.cancellation_requested', [
                'ride_id'   => $event->rideId,
                'driver_id' => $event->driverId
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnRideCancellationRequested failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId
            ]);
        }
    }
}
