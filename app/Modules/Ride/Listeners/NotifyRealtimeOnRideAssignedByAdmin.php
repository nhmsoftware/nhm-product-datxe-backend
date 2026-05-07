<?php

declare(strict_types=1);

namespace App\Modules\Ride\Listeners;

use App\Modules\Ride\Events\RideAssignedByAdmin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

final class NotifyRealtimeOnRideAssignedByAdmin implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(RideAssignedByAdmin $event): void
    {
        try {
            $payload = [
                'event'       => 'ride.assigned_by_admin',
                'ride_id'     => (string) $event->rideId,
                'user_id'     => (string) $event->driverId, // Notify the assigned driver
                'customer_id' => (string) $event->customerId,
                'driver_id'   => (string) $event->driverId,
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish to Redis
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: ride.assigned_by_admin', [
                'ride_id' => $event->rideId,
                'driver_id' => $event->driverId
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnRideAssignedByAdmin failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId
            ]);
        }
    }
}
