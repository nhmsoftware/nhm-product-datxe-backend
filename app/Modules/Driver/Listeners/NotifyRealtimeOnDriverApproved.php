<?php

declare(strict_types=1);

namespace App\Modules\Driver\Listeners;

use App\Modules\Driver\Events\DriverApplicationApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener xử lý thông báo realtime khi hồ sơ tài xế được duyệt.
 * Gửi tín hiệu qua Redis để nhm-realtime service chuyển tiếp tới frontend.
 */
final class NotifyRealtimeOnDriverApproved implements ShouldQueue
{
    /**
     * Tên Redis channel dùng chung cho các sự kiện truyền thông.
     */
    private const COMMUNICATION_CHANNEL = 'ride.communication.events';

    public function handle(DriverApplicationApproved $event): void
    {
        try {
            $payload = [
                'event'          => 'driver.application_approved',
                'user_id'        => $event->userId,
                'application_id' => $event->applicationId,
                'occurred_at'    => now()->toIso8601String(),
            ];

            // Publish to Redis channel expected by nhm-realtime service
            Redis::publish(self::COMMUNICATION_CHANNEL, json_encode($payload));

            Log::info('Realtime notification sent: driver.application_approved', [
                'user_id'        => $event->userId,
                'application_id' => $event->applicationId
            ]);
            
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnDriverApproved failed', [
                'error'          => $e->getMessage(),
                'user_id'        => $event->userId,
                'application_id' => $event->applicationId
            ]);
        }
    }
}
