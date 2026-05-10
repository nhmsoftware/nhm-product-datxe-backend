<?php

declare(strict_types=1);

namespace App\Modules\Order\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Order\DTO\GetOrderHistoryFilterDTO;

interface OrderServiceInterface
{
    /**
     * UC-19: View Order History (List)
     */
    public function getHistory(GetOrderHistoryFilterDTO $dto): ServiceReturn;

    /**
     * UC-70: View Order Detail
     */
    public function getOrderDetail(string $orderId, string $serviceType, ?string $merchantId = null): ServiceReturn;

    /**
     * UC-71: Accept Food Order
     */
    public function acceptFoodOrder(string $orderId, string $merchantId): ServiceReturn;

    /**
     * UC-72: Reject Food Order
     */
    public function rejectFoodOrder(string $orderId, string $merchantId, ?string $reason = null): ServiceReturn;

    /**
     * UC-64: Mark Order as Preparing
     */
    public function markPreparing(string $orderId, string $merchantId): ServiceReturn;

    /**
     * UC-73: Mark Order as Ready
     */
    public function markReady(string $orderId, string $merchantId): ServiceReturn;

    /**
     * UC-75: Cancel Food Order
     */
    public function cancelFoodOrder(string $orderId, string $merchantId, ?string $reason = null): ServiceReturn;

    /**
     * UC-74: Handle Cancellation Request
     */
    public function handleCancellation(string $orderId, string $merchantId, string $action): ServiceReturn;
}
