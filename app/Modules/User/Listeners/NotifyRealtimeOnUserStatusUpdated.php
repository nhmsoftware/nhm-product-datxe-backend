<?php

declare(strict_types=1);

namespace App\Modules\User\Listeners;

use App\Modules\User\Events\UserStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener xử lý gửi thông báo realtime khi trạng thái tài khoản thay đổi.
 * UC-78 Lock/Unlock User
 */
final class NotifyRealtimeOnUserStatusUpdated implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(UserStatusUpdated $event): void
    {
        try {
            $message = $event->isActive 
                ? 'Tài khoản của bạn đã được mở khóa. Bạn có thể tiếp tục sử dụng dịch vụ.'
                : 'Tài khoản của bạn tạm thời bị khóa' . ($event->reason ? ' do ' . $event->reason : '') . '. Vui lòng liên hệ tổng đài để được hỗ trợ.';

            $payload = [
                'event'           => 'user.status_updated',
                'user_id'         => (string) $event->userId,
                'is_active'       => $event->isActive,
                'lock_reason'     => $event->reason,
                'lock_expired_at' => $event->expiredAt,
                'message'         => $message,
                'occurred_at'     => now()->toIso8601String(),
            ];

            // Publish to Redis channel expected by the Node.js realtime service
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: user.status_updated', [
                'user_id'   => $event->userId,
                'is_active' => $event->isActive,
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnUserStatusUpdated failed', [
                'error'   => $e->getMessage(),
                'user_id' => $event->userId
            ]);
        }
    }
}
