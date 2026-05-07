<?php

declare(strict_types=1);

namespace App\Modules\User\Listeners;

use App\Modules\User\Events\DriverApplicationApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener xử lý gửi thông báo realtime khi hồ sơ tài xế được duyệt.
 * UC-81 Approve Driver
 */
final class NotifyRealtimeOnDriverApplicationApproved implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(DriverApplicationApproved $event): void
    {
        try {
            $payload = [
                'event'       => 'driver.application_approved',
                'user_id'     => (string) $event->userId,
                'message'     => 'Chúc mừng! Hồ sơ tài xế của bạn đã được duyệt.',
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish to Redis channel expected by the Node.js realtime service
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: driver.application_approved', [
                'user_id' => $event->userId,
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnDriverApplicationApproved failed', [
                'error'   => $e->getMessage(),
                'user_id' => $event->userId
            ]);
        }
    }
}
