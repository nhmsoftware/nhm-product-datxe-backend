<?php

declare(strict_types=1);

namespace App\Modules\Driver\Listeners;

use App\Modules\Driver\Events\DriverStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener đồng bộ trạng thái Tài xế lên Realtime server.
 */
final class NotifyRealtimeOnDriverStatusUpdated implements ShouldQueue
{
    public function handle(DriverStatusUpdated $event): void
    {
        try {
            $payload = [
                'event'     => 'driver.status.updated',
                'user_id'   => (string) $event->userId,
                'status'    => $event->status,
                'occurred_at' => now()->toIso8601String(),
            ];

            // Sử dụng channel config nếu có, nếu không mặc định ride.communication.events
            $channel = config('redis.communication.channel', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: driver.status.updated', [
                'user_id' => $event->userId,
                'status'  => $event->status
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnDriverStatusUpdated failed', [
                'error'   => $e->getMessage(),
                'user_id' => $event->userId
            ]);
        }
    }
}
