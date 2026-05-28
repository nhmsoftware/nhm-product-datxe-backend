<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Food\Model\Enums\FoodOrderStatus;
use App\Modules\Food\Model\FoodOrder;
use App\Modules\Order\DTO\GetMerchantOrdersFilterDTO;
use App\Modules\Order\DTO\GetOrderHistoryFilterDTO;
use App\Modules\Order\Interfaces\OrderRepositoryInterface;
use App\Modules\Order\Interfaces\OrderServiceInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Food\Interfaces\FoodOrderRepositoryInterface;

final class OrderService extends BaseService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly RideRepositoryInterface $rideRepository,
        private readonly FoodOrderRepositoryInterface $foodOrderRepository
    ) {}

    public function getHistory(GetOrderHistoryFilterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $paginator = $this->orderRepository->getHistory($dto);

            $paginator->getCollection()->transform(function ($item) {
                $itemArray = $item->toArray();
                $itemArray['status_label'] = $this->getStatusLabel($itemArray['service_type'], (int) $itemArray['status']);
                $itemArray['service_name'] = match ($itemArray['service_type']) {
                    'ride' => 'Nội thành',
                    'intercity' => 'Đi tỉnh',
                    'airport' => 'Sân bay',
                    'delivery' => 'Giao hàng',
                    'chauffeur' => 'Lái hộ',
                    'food' => 'Giao đồ ăn',
                    default => 'Không xác định'
                };
                return $itemArray;
            });

            return $paginator;
        });
    }

    public function getOrderDetail(string $orderId, string $serviceType, ?string $merchantId = null): ServiceReturn
    {
        return $this->execute(function () use ($orderId, $serviceType, $merchantId) {
            $order = null;

            if (in_array($serviceType, ['ride', 'delivery', 'intercity', 'airport', 'chauffeur'])) {
                $ride = $this->rideRepository->find($orderId);
                if ($ride) {
                    $ride->load('customer.customerProfile');
                    $order = $ride->toArray();
                    $order['customer_name'] = $ride->customer?->customerProfile?->full_name ?? 'Khách hàng';
                }
            } elseif ($serviceType === 'food') {
                $order = $this->foodOrderRepository->getDetail($orderId, $merchantId);
            }

            $this->validate($order !== null, 'Không tìm thấy đơn hàng hoặc bạn không có quyền truy cập.', 404);

            $order['status_label'] = $this->getStatusLabel($serviceType, (int) $order['status']);
            $order['service_name'] = match ($serviceType) {
                'ride' => 'Nội thành',
                'intercity' => 'Đi tỉnh',
                'airport' => 'Sân bay',
                'delivery' => 'Giao hàng',
                'chauffeur' => 'Lái hộ',
                'food' => 'Giao đồ ăn',
                default => 'Không xác định'
            };

            return $order;
        });
    }

    private function getStatusLabel(string $type, int $status): string
    {
        if (in_array($type, ['ride', 'delivery', 'intercity', 'airport', 'chauffeur'])) {
            $enum = RideStatus::tryFrom($status);
            return $enum ? $enum->getLabel() : 'Không xác định';
        }

        if ($type === 'food') {
            $enum = FoodOrderStatus::tryFrom($status);
            return $enum ? $enum->getLabel() : 'Không xác định';
        }

        return 'Không xác định';
    }

    /**
     * UC-71: Accept Food Order
     */
    public function acceptFoodOrder(string $orderId, string $merchantId): ServiceReturn
    {
        return $this->updateStatus($orderId, $merchantId, FoodOrderStatus::PENDING, FoodOrderStatus::CONFIRMED);
    }

    /**
     * UC-72: Reject Food Order (Nhà hàng từ chối đơn PENDING — thường chưa có tài xế)
     * Nếu đã có ride được gán, cũng cancel ride và notify tài xế (không tính lỗi cho tài xế).
     */
    public function rejectFoodOrder(string $orderId, string $merchantId, ?string $reason = null): ServiceReturn
    {
        return $this->cancelOrderByMerchant($orderId, $merchantId, FoodOrderStatus::PENDING, $reason);
    }

    public function markPreparing(string $orderId, string $merchantId): ServiceReturn
    {
        return $this->updateStatus($orderId, $merchantId, FoodOrderStatus::CONFIRMED, FoodOrderStatus::PREPARING);
    }

    /**
     * UC-73: Mark Order as Ready
     */
    public function markReady(string $orderId, string $merchantId): ServiceReturn
    {
        return $this->updateStatus($orderId, $merchantId, [FoodOrderStatus::CONFIRMED, FoodOrderStatus::PREPARING], FoodOrderStatus::READY);
    }

    /**
     * UC-75: Cancel Food Order (Nhà hàng hủy đơn đang xử lý).
     * Nếu đã có tài xế nhận đơn, hệ thống sẽ:
     *   1. Cancel ride tương ứng.
     *   2. Notify tài xế qua realtime.
     *   3. KHÔNG tính lỗi hủy cho tài xế vì đây là lỗi phía nhà hàng.
     */
    public function cancelFoodOrder(string $orderId, string $merchantId, ?string $reason = null): ServiceReturn
    {
        return $this->cancelOrderByMerchant($orderId, $merchantId, null, $reason);
    }

    /**
     * Hàm nội bộ: Hủy đơn hàng bởi nhà hàng.
     * Xử lý đồng thời: hủy ride (nếu có) và notify tài xế (không tính lỗi cho tài xế).
     *
     * @param string $orderId
     * @param string $merchantId
     * @param FoodOrderStatus|null $requiredStatus Nếu null, bỏ qua kiểm tra trạng thái (chỉ check isTerminal).
     * @param string|null $reason
     */
    private function cancelOrderByMerchant(string $orderId, string $merchantId, ?FoodOrderStatus $requiredStatus, ?string $reason): ServiceReturn
    {
        return $this->execute(function () use ($orderId, $merchantId, $requiredStatus, $reason) {
            // 1. Load đơn hàng kèm thông tin ride để lấy driver_id
            $order = FoodOrder::with('ride')
                ->where('id', $orderId)
                ->first();

            $this->validate($order !== null, 'Không tìm thấy đơn hàng.', 404);
            $this->validate((string)$order->merchant_id === $merchantId, 'Bạn không có quyền xử lý đơn hàng này.', 403);

            $currentStatus = $order->status; // Enum vì cast
            if ($requiredStatus !== null) {
                $this->validate(
                    $currentStatus === $requiredStatus,
                    'Trạng thái đơn hàng không hợp lệ để thực hiện hành động này.',
                    400
                );
            } else {
                $this->validate(
                    $currentStatus && !$currentStatus->isTerminal(),
                    'Không thể hủy đơn hàng đã hoàn thành hoặc đã hủy.',
                    400
                );
            }

            // 2. Lấy driver_id từ ride (nếu có)
            $driverId = null;
            $ride = $order->ride;
            if ($ride && $ride->driver_id) {
                $driverId = (string) $ride->driver_id;

                // Cancel ride — KHÔNG tăng cancel_count của tài xế
                // Dùng updateStatus trực tiếp thay vì cancelByDriver để tránh logic phạt tài xế
                $this->rideRepository->updateStatus(
                    (string) $ride->id,
                    RideStatus::CANCELLED,
                    $reason ?? 'Nhà hàng hủy đơn'
                );
            }

            // 3. Cập nhật trạng thái đơn hàng
            $this->foodOrderRepository->updateFoodOrderStatus($orderId, FoodOrderStatus::CANCELLED->value);

            // 4. Dispatch event — truyền driverId để listener gửi notify cho tài xế
            event(new \App\Modules\Order\Events\FoodOrderStatusUpdated(
                $orderId,
                (string) $order->customer_id,
                FoodOrderStatus::CANCELLED->value,
                $currentStatus->value,
                $reason,
                $driverId  // Tài xế cần được notify (nullable)
            ));

            return true;
        }, useTransaction: true);
    }

    /**
     * UC-74: Handle Cancellation Request
     */
    public function handleCancellation(string $orderId, string $merchantId, string $action): ServiceReturn
    {
        return $this->execute(function () use ($orderId, $merchantId, $action) {
            $order = $this->foodOrderRepository->getDetail($orderId);

            $this->validate($order !== null, 'Không tìm thấy đơn hàng.', 404);
            $this->validate($order['merchant_id'] === $merchantId, 'Bạn không có quyền xử lý đơn hàng này.', 403);

            // UC-74: Prerequisite = Accepted (CONFIRMED/PREPARING) or Ready
            $allowedStatuses = [
                FoodOrderStatus::CONFIRMED->value,
                FoodOrderStatus::PREPARING->value,
                FoodOrderStatus::READY->value
            ];
            $this->validate(in_array((int)$order['status'], $allowedStatuses, true), 'Không thể xử lý yêu cầu hủy cho đơn hàng này.', 400);

            if ($action === 'accept') {
                return $this->cancelFoodOrder($orderId, $merchantId, $order['cancel_request_reason'] ?? 'Merchant accepted cancellation request');
            }

            // action === 'reject'
            $this->foodOrderRepository->resetCancellationRequest($orderId);

            // Dispatch Event
            event(new \App\Modules\Order\Events\FoodCancellationRequestHandled($orderId, (string)$order['customer_id'], 'rejected'));

            return true;
        }, useTransaction: true);
    }

    private function updateStatus(string $orderId, string $merchantId, FoodOrderStatus|array $requiredStatus, FoodOrderStatus $nextStatus, ?string $reason = null): ServiceReturn
    {
        return $this->execute(function () use ($orderId, $merchantId, $requiredStatus, $nextStatus, $reason) {
            $order = $this->foodOrderRepository->getDetail($orderId);

            $this->validate($order !== null, 'Không tìm thấy đơn hàng.', 404);
            $this->validate($order['merchant_id'] === $merchantId, 'Bạn không có quyền xử lý đơn hàng này.', 403);

            $currentStatus = (int)$order['status'];
            $allowed = is_array($requiredStatus)
                ? array_map(fn($s) => $s->value, $requiredStatus)
                : [$requiredStatus->value];

            $this->validate(in_array($currentStatus, $allowed, true), "Trạng thái đơn hàng không hợp lệ để thực hiện hành động này.", 400);

            $this->foodOrderRepository->updateFoodOrderStatus($orderId, $nextStatus->value);

            // Dispatch Event
            event(new \App\Modules\Order\Events\FoodOrderStatusUpdated($orderId, (string)$order['customer_id'], $nextStatus->value, $currentStatus, $reason));

            return true;
        }, useTransaction: true);
    }

    /**
     * UC-69.1: View all Order (Merchant)
     */
    public function getMerchantOrders(GetMerchantOrdersFilterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $statuses = null;
            if ($dto->statusGroup) {
                $statuses = match ($dto->statusGroup) {
                    'new' => [FoodOrderStatus::PENDING->value],
                    'preparing' => [
                        FoodOrderStatus::CONFIRMED->value,
                        FoodOrderStatus::PREPARING->value,
                        FoodOrderStatus::READY->value,
                    ],
                    'processed' => [
                        FoodOrderStatus::PICKED_UP->value,
                        FoodOrderStatus::DELIVERED->value,
                        FoodOrderStatus::CANCELLED->value,
                    ],
                    default => $this->throw('Nhóm trạng thái không hợp lệ.', 400),
                };
            }

            // Get paginated orders
            $paginator = $this->foodOrderRepository->getMerchantOrders(
                $dto->merchantId,
                $statuses,
                $dto->perPage,
                $dto->page
            );

            // Fetch overview stats (for today)
            $totalOrdersToday = $this->foodOrderRepository->countOrdersByMerchant($dto->merchantId, 'today');
            $revenueToday = $this->foodOrderRepository->sumRevenueByMerchant($dto->merchantId, 'today');
            $completedOrdersToday = $this->foodOrderRepository->countCompletedOrdersByMerchant($dto->merchantId, 'today');

            $performance = $totalOrdersToday > 0
                ? round(($completedOrdersToday / $totalOrdersToday) * 100, 2)
                : 100.0;

            // Transform collection to array with status labels and customer name
            $paginator->getCollection()->transform(function ($item) {
                $itemArray = $item->toArray();
                $itemArray['status_label'] = $item->status ? $item->status->getLabel() : 'Không xác định';
                $itemArray['customer_name'] = $item->customer?->customerProfile?->full_name ?? 'Khách hàng';
                return $itemArray;
            });

            return [
                'overview' => [
                    'total_orders_today' => $totalOrdersToday,
                    'revenue_today' => $revenueToday,
                    'performance_today' => $performance,
                ],
                'orders' => $paginator,
            ];
        });
    }
}

