<?php

declare(strict_types=1);

namespace App\Modules\Food\Listeners;

use App\Modules\Food\Events\FoodOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;

/**
 * UC-18: Gửi thông báo đơn hàng mới tới Merchant qua Realtime Server.
 */
final class NotifyMerchantOnFoodOrderCreated implements ShouldQueue
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepository
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(FoodOrderCreated $event): void
    {
        try {
            $merchant = $this->merchantRepository->findById($event->merchantId);
            if (!$merchant) {
                Log::warning('NotifyMerchantOnFoodOrderCreated skipped: Merchant not found', [
                    'merchant_id' => $event->merchantId,
                ]);
                return;
            }

            $payload = [
                'event' => 'food.order_created',
                'order_id' => $event->orderId,
                'customer_id' => $event->customerId,
                'merchant_id' => $event->merchantId,
                'user_id' => (string) $merchant->user_id, // Get the actual user_id of the merchant owner
                'total_price' => $event->totalPrice,
                'message' => 'Bạn có đơn hàng mới!',
                'occurred_at' => now()->toIso8601String(),
            ];

            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: food.order_created', [
                'order_id' => $event->orderId,
                'merchant_id' => $event->merchantId,
                'user_id' => $merchant->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyMerchantOnFoodOrderCreated failed', [
                'error' => $e->getMessage(),
                'order_id' => $event->orderId,
            ]);
        }
    }
}
