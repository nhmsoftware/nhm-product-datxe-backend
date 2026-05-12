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
     * Count total orders for a merchant by period (today, week, month).
     * UC-66 View order statistics
     */
    public function countOrdersByMerchant(string $merchantId, string $period = 'today'): int;

    /**
     * Sum total revenue for a merchant by period (Completed orders).
     * UC-67 View revenue statistics
     */
    public function sumRevenueByMerchant(string $merchantId, string $period = 'today'): float;

    /**
     * Count completed orders for a merchant by period.
     * UC-67.a View average order value
     */
    public function countCompletedOrdersByMerchant(string $merchantId, string $period = 'today'): int;

    /**
     * Get aggregated revenue data for merchant chart.
     * UC-67.b View revenue chart
     */
    public function getRevenueChartData(string $merchantId, string $period = 'today'): array;

    /**
     * Update status of a food order.
     *
     * @param string $orderId
     * @param int $status
     * @return bool
     */
    public function updateFoodOrderStatus(string $orderId, int $status): bool;
}
