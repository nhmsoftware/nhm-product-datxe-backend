<?php

declare(strict_types=1);

namespace App\Modules\Driver\Listeners;

use App\Modules\Driver\Events\RideStarted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener xử lý việc thông báo Realtime cho Khách hàng khi Chuyến xe bắt đầu di chuyển.
 */
final class NotifyRealtimeOnRideStarted implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(RideStarted $event): void
    {
        try {
            $payload = [
                'event'     => 'ride.started',
                'ride_id'   => (string) $event->rideId,
                'driver_id' => (string) $event->driverId,
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish vào Redis channel dành cho Communication events
            Redis::publish('ride.communication.events', json_encode($payload));

            Log::info('Realtime notification sent: ride.started', [
                'ride_id'   => $event->rideId,
                'driver_id' => $event->driverId
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnRideStarted failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId
            ]);
        }
    }
}
