<?php

declare(strict_types=1);

namespace App\Modules\Operation\Listeners;

use App\Modules\Operation\Events\UserLocationUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener xử lý việc phát vị trí người dùng lên Redis Channel.
 * Tương ứng với nhm-realtime service để tracking Driver/Customer trên bản đồ.
 */
final class NotifyRealtimeOnLocationUpdated implements ShouldQueue
{
    /**
     * Tên Redis channel dành riêng cho việc Tracking (Location).
     */
    private const TRACKING_CHANNEL = 'ride.tracking.events';

    /**
     * Handle the event.
     */
    public function handle(UserLocationUpdated $event): void
    {
        try {
            $payload = [
                'ride_id' => $event->rideId ? (string) $event->rideId : null,
                'user_id' => (string) $event->userId,
                'role'    => (int) $event->role,
                'location' => [
                    'lat'        => (float) $event->lat,
                    'lng'        => (float) $event->lng,
                    'tracked_at' => now()->toIso8601String(),
                ],
            ];

            // Chỉ broadcast nếu có ride_id (đang trong chuyến) hoặc logic yêu cầu theo dõi tự do
            // Ở đây ta cứ broadcast, Realtime server sẽ quyết định route tới đâu.
            Redis::publish(self::TRACKING_CHANNEL, json_encode($payload));

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnLocationUpdated failed', [
                'error'   => $e->getMessage(),
                'user_id' => $event->userId
            ]);
        }
    }
}
