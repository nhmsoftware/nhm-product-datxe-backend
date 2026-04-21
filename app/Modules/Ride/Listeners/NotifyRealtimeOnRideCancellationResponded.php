<?php

declare(strict_types=1);

namespace App\Modules\Ride\Listeners;

use App\Modules\Ride\Events\RideCancellationResponded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

final class NotifyRealtimeOnRideCancellationResponded implements ShouldQueue
{
    public function handle(RideCancellationResponded $event): void
    {
        try {
            $payload = [
                'event'       => 'ride.cancellation_responded',
                'ride_id'     => (string) $event->rideId,
                'user_id'     => (string) $event->customerId, // Send directly to Customer's userRoom
                'driver_id'   => (string) $event->driverId,
                'customer_id' => (string) $event->customerId,
                'is_approved' => $event->isApproved,
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish to Redis channel expected by nhm-realtime service
            Redis::publish('ride.communication.events', json_encode($payload));

            Log::info('Realtime notification sent: ride.cancellation_responded', [
                'ride_id'     => $event->rideId,
                'is_approved' => $event->isApproved
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnRideCancellationResponded failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId
            ]);
        }
    }
}
