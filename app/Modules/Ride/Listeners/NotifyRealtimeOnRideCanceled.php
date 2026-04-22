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
                'user_id'     => $event->driverId ? (string) $event->driverId : (string) $event->customerId, // Route to counterparty if possible, default to customer
                'customer_id' => (string) $event->customerId,
                'driver_id'   => $event->driverId ? (string) $event->driverId : null,
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish to Redis channel expected by the Node.js realtime service
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

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
