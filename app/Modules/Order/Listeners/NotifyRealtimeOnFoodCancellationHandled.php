<?php

declare(strict_types=1);

namespace App\Modules\Order\Listeners;

use App\Modules\Order\Events\FoodCancellationRequestHandled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener thông báo cho Khách hàng khi Merchant xử lý yêu cầu hủy.
 * UC-74
 */
final class NotifyRealtimeOnFoodCancellationHandled implements ShouldQueue
{
    public function handle(FoodCancellationRequestHandled $event): void
    {
        try {
            $payload = [
                'event'    => 'food_order.cancellation_handled',
                'order_id' => $event->orderId,
                'user_id'  => $event->customerId,
                'action'   => $event->action,
                'message'  => $event->action === 'accepted' 
                    ? "Yêu cầu hủy đơn hàng {$event->orderId} đã được chấp nhận."
                    : "Yêu cầu hủy đơn hàng {$event->orderId} đã bị từ chối.",
                'occurred_at' => now()->toIso8601String(),
            ];

            // Emit to Node.js WebSocket via Redis
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: food_order.cancellation_handled', [
                'order_id' => $event->orderId,
                'action'   => $event->action
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnFoodCancellationHandled failed', [
                'error' => $e->getMessage(),
                'order_id' => $event->orderId
            ]);
        }
    }
}
