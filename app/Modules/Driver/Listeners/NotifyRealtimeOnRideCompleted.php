<?php

declare(strict_types=1);

namespace App\Modules\Driver\Listeners;

use App\Modules\Driver\Events\RideCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener xử lý việc thông báo Realtime cho Khách hàng khi Chuyến xe hoàn thành.
 */
final class NotifyRealtimeOnRideCompleted implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(RideCompleted $event): void
    {
        try {
            $payload = [
                'event'      => 'ride.completed',
                'ride_id'    => (string) $event->rideId,
                'driver_id'  => (string) $event->driverId,
                'total_fare' => (float) $event->totalFare,
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish vào Redis channel dành cho Communication events
            Redis::publish('ride.communication.events', json_encode($payload));

            Log::info('Realtime notification sent: ride.completed', [
                'ride_id'   => $event->rideId,
                'driver_id' => $event->driverId,
                'fare'      => $event->totalFare
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnRideCompleted failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId
            ]);
        }
    }
}
