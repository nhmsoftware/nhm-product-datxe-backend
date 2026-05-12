<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Listeners;

use App\Modules\RiskManagement\Events\UserWarned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

final class NotifyRealtimeOnUserWarned implements ShouldQueue
{
    public function handle(UserWarned $event): void
    {
        try {
            $payload = [
                'event' => 'user.warned',
                'user_id' => $event->userId,
                'violation_id' => $event->violationId,
                'type' => $event->type,
                'reason' => $event->reason,
                'violation_count' => $event->violationCount,
                'message' => 'Bạn vừa nhận được một cảnh báo: ' . $event->reason,
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish to Redis channel
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: user.warned', [
                'user_id' => $event->userId,
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnUserWarned failed', [
                'error' => $e->getMessage(),
                'user_id' => $event->userId
            ]);
        }
    }
}
