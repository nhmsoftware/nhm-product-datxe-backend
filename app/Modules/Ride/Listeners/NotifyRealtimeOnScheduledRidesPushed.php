<?php

declare(strict_types=1);

namespace App\Modules\Ride\Listeners;

use App\Modules\Ride\Events\ScheduledRidesPushedToPool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

final class NotifyRealtimeOnScheduledRidesPushed implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(ScheduledRidesPushedToPool $event): void
    {
        try {
            $payload = [
                'event'       => 'ride.scheduled_pushed_to_pool',
                'ride_ids'    => $event->rideIds,
                'user_id'     => 'all_drivers', // Special identifier for broad broadcast if needed
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish to Redis
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: ride.scheduled_pushed_to_pool', [
                'count' => count($event->rideIds),
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnScheduledRidesPushed failed', [
                'error'   => $e->getMessage()
            ]);
        }
    }
}
