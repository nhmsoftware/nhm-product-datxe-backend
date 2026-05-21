<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Listeners;

use App\Modules\Merchant\Events\MerchantApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * UC-86: Relay merchant approval event to realtime service via Redis.
 */
final class NotifyRealtimeOnMerchantApproved implements ShouldQueue
{
    public function handle(MerchantApproved $event): void
    {
        try {
            $payload = [
                'event'        => 'merchant.approved',
                'user_id'      => (string) $event->userId,
                'merchant_id'  => (string) $event->merchantId,
                'status'       => 2,
                'status_label' => 'Đã duyệt',
                'message'      => 'Chúc mừng! Hồ sơ Merchant của bạn đã được duyệt.',
                'occurred_at'  => now()->toIso8601String(),
            ];

            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: merchant.approved', [
                'user_id'     => $event->userId,
                'merchant_id' => $event->merchantId,
                'channel'     => $channel,
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnMerchantApproved failed', [
                'error'       => $e->getMessage(),
                'user_id'     => $event->userId,
                'merchant_id' => $event->merchantId,
            ]);
        }
    }
}
