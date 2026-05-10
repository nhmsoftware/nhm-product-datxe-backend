<?php

declare(strict_types=1);

namespace App\Modules\Order\Interfaces;

use App\Modules\Order\DTO\GetOrderHistoryFilterDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    /**
     * Get unified order history for a customer
     */
    public function getHistory(GetOrderHistoryFilterDTO $filters): LengthAwarePaginator;

    /**
     * Get specific order detail from either ride or food
     */
    public function getDetail(string $orderId, string $serviceType, ?string $merchantId = null): ?array;

    /**
     * Count total orders for a merchant in the current day.
     * UC-66 View total daily orders
     */
    public function countDailyOrdersByMerchant(string $merchantId): int;

    /**
     * Sum total revenue for a merchant in the current day (Completed orders).
     * UC-67 View daily revenue
     */
    public function sumDailyRevenueByMerchant(string $merchantId): float;

    /**
     * Update status of a food order.
     *
     * @param string $orderId
     * @param int $status
     * @return bool
     */
    public function updateFoodOrderStatus(string $orderId, int $status): bool;
}
