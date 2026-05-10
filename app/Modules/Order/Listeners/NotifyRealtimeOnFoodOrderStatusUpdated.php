<?php

declare(strict_types=1);

namespace App\Modules\Order\Listeners;

use App\Modules\Order\Events\FoodOrderStatusUpdated;
use App\Modules\Food\Model\Enums\FoodOrderStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener thông báo cho Khách hàng khi trạng thái đơn hàng thay đổi.
 * UC-71, UC-72, v.v.
 */
final class NotifyRealtimeOnFoodOrderStatusUpdated implements ShouldQueue
{
    public function handle(FoodOrderStatusUpdated $event): void
    {
        try {
            $statusEnum = FoodOrderStatus::tryFrom($event->newStatus);
            $statusLabel = $statusEnum ? $statusEnum->getLabel() : 'Cập nhật trạng thái';

            $payload = [
                'event'    => 'food_order.updated',
                'order_id' => $event->orderId,
                'user_id'  => $event->customerId, // Để Node.js emit vào user room
                'status'   => $event->newStatus,
                'message'  => "Đơn hàng {$event->orderId} đã thay đổi trạng thái sang: {$statusLabel}.",
                'reason'   => $event->reason,
                'occurred_at' => now()->toIso8601String(),
            ];

            // Emit to Node.js WebSocket via Redis
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: food_order.updated', [
                'order_id' => $event->orderId,
                'status'   => $event->newStatus
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnFoodOrderStatusUpdated failed', [
                'error' => $e->getMessage(),
                'order_id' => $event->orderId
            ]);
        }
    }
}
