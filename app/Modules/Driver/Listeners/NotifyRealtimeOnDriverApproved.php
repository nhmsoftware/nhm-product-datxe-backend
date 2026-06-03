<?php

declare(strict_types=1);

namespace App\Modules\Driver\Listeners;

use App\Modules\Driver\Events\DriverApplicationApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\Driver\Notifications\DriverApplicationApprovedNotification;

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

    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}


    public function handle(DriverApplicationApproved $event): void
    {
        try {
            $payload = [
                'event'          => 'driver.application_approved',
                'user_id'        => (string) $event->userId,
                'application_id' => (string) $event->applicationId,
                'occurred_at'    => now()->toIso8601String(),
            ];

            // Sử dụng connection 'default' và gọi publish trực tiếp để tránh Laravel Prefix
            Redis::connection('default')->publish(
                self::COMMUNICATION_CHANNEL,
                json_encode($payload)
            );

            // Gửi thông báo vào Database để lưu trữ (Persistence)
            $user = $this->userRepository->findById($event->userId);
            if ($user) {
                $user->notify(new DriverApplicationApprovedNotification($event->applicationId));
            }


            Log::info('Realtime notification sent: driver.application_approved', [
                'user_id'        => $event->userId,
                'application_id' => $event->applicationId,
                'channel'        => self::COMMUNICATION_CHANNEL
            ]);

        } catch (\Throwable $e) {
            Log::error('NotifyRealtimeOnDriverApproved failed', [
                'error'          => $e->getMessage(),
                'user_id'        => $event->userId,
                'application_id' => $event->applicationId
            ]);
        }
    }
}
