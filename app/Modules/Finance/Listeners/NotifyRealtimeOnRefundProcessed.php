<?php

declare(strict_types=1);

namespace App\Modules\Finance\Listeners;

use App\Modules\Finance\Events\RefundProcessed;
use App\Modules\Finance\Interfaces\RefundRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

final class NotifyRealtimeOnRefundProcessed implements ShouldQueue
{
    public function __construct(
        private readonly RefundRepositoryInterface $refundRepository,
    ) {}

    public function handle(RefundProcessed $event): void
    {
        try {
            $refund = $this->refundRepository->find($event->refundId);
            if (!$refund) return;

            $payload = [
                'event' => 'finance.refund.processed',
                'user_id' => (string) $refund->customer_id,
                'refund_id' => (string) $event->refundId,
                'status' => $event->status,
                'amount' => $event->amount,
                'message' => 'Yêu cầu hoàn tiền của bạn đã được ' . ($event->status === 'APPROVED' ? 'phê duyệt' : ($event->status === 'REJECTED' ? 'từ chối' : 'hoàn tất')),
                'occurred_at' => $event->processedAt,
            ];

            $channel = env('REDIS_FINANCE_CHANNEL', 'finance.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: finance.refund.processed', [
                'refund_id' => $event->refundId,
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnRefundProcessed failed', [
                'error' => $e->getMessage(),
                'refund_id' => $event->refundId
            ]);
        }
    }
}
