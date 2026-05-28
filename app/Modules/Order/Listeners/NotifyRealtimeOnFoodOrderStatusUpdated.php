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
 * Nếu đơn hàng bị hủy bởi Nhà hàng và có Tài xế đang thực hiện,
 * cũng gửi notify cho Tài xế (không tính lỗi hủy cho tài xế).
 * UC-71, UC-72, UC-75, v.v.
 */
final class NotifyRealtimeOnFoodOrderStatusUpdated implements ShouldQueue
{
    public function __construct(
        private readonly \App\Modules\Food\Interfaces\FoodOrderRepositoryInterface $foodOrderRepository,
        private readonly \App\Modules\Merchant\Interfaces\MerchantRepositoryInterface $merchantRepository
    ) {}

    public function handle(FoodOrderStatusUpdated $event): void
    {
        $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');

        try {
            $statusEnum = FoodOrderStatus::tryFrom($event->newStatus);
            $statusLabel = $statusEnum ? $statusEnum->getLabel() : 'Cập nhật trạng thái';

            // --- Thông báo cho Khách hàng ---
            $customerPayload = [
                'event'    => 'food_order.updated',
                'order_id' => $event->orderId,
                'user_id'  => $event->customerId,
                'status'   => $event->newStatus,
                'message'  => "Đơn hàng {$event->orderId} đã thay đổi trạng thái sang: {$statusLabel}.",
                'reason'   => $event->reason,
                'occurred_at' => now()->toIso8601String(),
            ];
            Redis::publish($channel, json_encode($customerPayload));

            Log::info('Realtime notification sent to customer: food_order.updated', [
                'order_id' => $event->orderId,
                'status'   => $event->newStatus,
                'customer_id' => $event->customerId,
            ]);

            // --- Thông báo cho Merchant ---
            $order = $this->foodOrderRepository->getDetail($event->orderId);
            if ($order && !empty($order['merchant_id'])) {
                $merchantProfile = $this->merchantRepository->findById((string) $order['merchant_id']);
                if ($merchantProfile) {
                    $merchantPayload = [
                        'event'    => 'food_order.updated',
                        'order_id' => $event->orderId,
                        'user_id'  => (string) $merchantProfile->user_id,
                        'merchant_id' => (string) $order['merchant_id'],
                        'status'   => $event->newStatus,
                        'message'  => "Đơn hàng {$event->orderId} đã thay đổi trạng thái sang: {$statusLabel}.",
                        'reason'   => $event->reason,
                        'occurred_at' => now()->toIso8601String(),
                    ];
                    Redis::publish($channel, json_encode($merchantPayload));

                    Log::info('Realtime notification sent to merchant: food_order.updated', [
                        'order_id' => $event->orderId,
                        'status'   => $event->newStatus,
                        'merchant_id' => $order['merchant_id'],
                        'user_id'  => $merchantProfile->user_id,
                    ]);
                }
            }

            // --- Thông báo cho Tài xế (nếu nhà hàng hủy đơn khi đã có tài xế nhận) ---
            if ($event->newStatus === FoodOrderStatus::CANCELLED->value && $event->driverId !== null) {
                $driverPayload = [
                    'event'    => 'food_order.cancelled_by_merchant',
                    'order_id' => $event->orderId,
                    'user_id'  => $event->driverId, // Node.js emit vào driver room
                    'status'   => $event->newStatus,
                    'message'  => 'Nhà hàng đã hủy đơn. Đơn này không được tính vào lượt hủy của bạn.',
                    'reason'   => $event->reason,
                    'occurred_at' => now()->toIso8601String(),
                ];
                Redis::publish($channel, json_encode($driverPayload));

                Log::info('Realtime notification sent to driver: food_order.cancelled_by_merchant', [
                    'order_id'  => $event->orderId,
                    'driver_id' => $event->driverId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnFoodOrderStatusUpdated failed', [
                'error'    => $e->getMessage(),
                'order_id' => $event->orderId,
            ]);
        }
    }
}
