<?php

declare(strict_types=1);

namespace App\Modules\Finance\Listeners;

use App\Modules\Finance\Events\TopUpCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * UC-45: Broadcast wallet.top_up.completed event qua Redis → finance.events → Socket.io → App Tài xế.
 * Payload đầy đủ snapshot để Frontend không cần gọi thêm API.
 */
final class NotifyRealtimeOnTopUpCompleted implements ShouldQueue
{
    public function handle(TopUpCompleted $event): void
    {
        try {
            $payload = [
                'event'          => 'wallet.top_up.completed',
                'user_id'        => $event->userId,
                'top_up_id'      => $event->topUpId,
                'amount'         => $event->amount,
                'balance'        => $event->balanceAfter,
                'payment_method' => $event->paymentMethod,
                'message'        => 'Nạp tiền thành công. Số dư ví đã được cập nhật.',
                'occurred_at'    => now()->toIso8601String(),
            ];

            $channel = env('REDIS_FINANCE_CHANNEL', 'finance.events');
            Redis::publish($channel, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            Log::info('Realtime notification sent: wallet.top_up.completed', [
                'top_up_id' => $event->topUpId,
                'user_id'   => $event->userId,
                'amount'    => $event->amount,
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnTopUpCompleted failed', [
                'error'      => $e->getMessage(),
                'top_up_id'  => $event->topUpId,
                'user_id'    => $event->userId,
            ]);
        }
    }
}
