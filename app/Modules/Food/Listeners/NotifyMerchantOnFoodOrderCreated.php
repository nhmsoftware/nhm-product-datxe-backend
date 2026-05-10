<?php

declare(strict_types=1);

namespace App\Modules\Food\Listeners;

use App\Modules\Food\Events\FoodOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * UC-18: Gửi thông báo đơn hàng mới tới Merchant qua Realtime Server.
 */
final class NotifyMerchantOnFoodOrderCreated implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(FoodOrderCreated $event): void
    {
        try {
            $payload = [
                'event' => 'food.order_created',
                'order_id' => $event->orderId,
                'customer_id' => $event->customerId,
                'merchant_id' => $event->merchantId,
                'total_price' => $event->totalPrice,
                'message' => 'Bạn có đơn hàng mới!',
                'occurred_at' => now()->toIso8601String(),
            ];

            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: food.order_created', [
                'order_id' => $event->orderId,
                'merchant_id' => $event->merchantId,
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyMerchantOnFoodOrderCreated failed', [
                'error' => $e->getMessage(),
                'order_id' => $event->orderId,
            ]);
        }
    }
}
