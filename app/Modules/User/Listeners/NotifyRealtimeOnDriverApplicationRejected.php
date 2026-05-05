<?php

declare(strict_types=1);

namespace App\Modules\User\Listeners;

use App\Modules\User\Events\DriverApplicationRejected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener xử lý gửi thông báo realtime khi hồ sơ tài xế bị từ chối.
 * UC-82 Reject Driver
 */
final class NotifyRealtimeOnDriverApplicationRejected implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(DriverApplicationRejected $event): void
    {
        try {
            $payload = [
                'event'       => 'driver.application_rejected',
                'user_id'     => (string) $event->userId,
                'reason'      => $event->reason,
                'message'     => 'Rất tiếc! Hồ sơ tài xế của bạn đã bị từ chối.',
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish to Redis channel expected by the Node.js realtime service
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: driver.application_rejected', [
                'user_id' => $event->userId,
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnDriverApplicationRejected failed', [
                'error'   => $e->getMessage(),
                'user_id' => $event->userId
            ]);
        }
    }
}
