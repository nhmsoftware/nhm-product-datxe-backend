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
     * Xử lý sự kiện khi có đơn hàng mới được tạo.
     */
    public function handle(FoodOrderCreated $event): void
    {
        try {
            // Lấy thông tin cửa hàng để tìm ra user_id (ID của chủ cửa hàng)
            $merchantProfile = $this->merchantRepository->findById($event->merchantId);
            if (!$merchantProfile) {
                Log::warning('Bỏ qua gửi thông báo socket: Không tìm thấy hồ sơ cửa hàng', [
                    'merchant_id' => $event->merchantId,
                ]);
                return;
            }

            // Node.js server yêu cầu key 'user_id' để có thể phát event vào đúng room của chủ cửa hàng (user:{user_id})
            $payload = [
                'event' => 'food.order_created',
                'order_id' => $event->orderId,
                'customer_id' => $event->customerId,
                'merchant_id' => $event->merchantId,
                'user_id' => (string) $merchantProfile->user_id, // Lấy đúng ID của chủ cửa hàng
                'total_price' => $event->totalPrice,
                'message' => 'Bạn có đơn đặt món mới, vui lòng kiểm tra!',
                'occurred_at' => now()->toIso8601String(),
            ];

            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Đã gửi thông báo socket đơn hàng mới cho nhà hàng', [
                'order_id' => $event->orderId,
                'merchant_id' => $event->merchantId,
                'user_id' => $merchantProfile->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi gửi thông báo socket cho nhà hàng', [
                'error' => $e->getMessage(),
                'order_id' => $event->orderId,
            ]);
        }
    }
}
