<?php

declare(strict_types=1);

namespace App\Modules\Food\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Food\Model\FoodOrder;

interface FoodOrderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * UC-18: Create food order with items and options
     *
     * @param array $orderData
     * @param array $itemsData
     * @return FoodOrder
     */
    public function createOrder(array $orderData, array $itemsData): FoodOrder;

    /**
     * Get food order detailed data with items and options.
     *
     * @param string $orderId
     * @param string|null $merchantId
     * @return array|null
     */
    public function getDetail(string $orderId, ?string $merchantId = null): ?array;

    /**
     * Count food orders by merchant.
     *
     * @param string $merchantId
     * @param string $period
     * @return int
     */
    public function countOrdersByMerchant(string $merchantId, string $period = 'today'): int;

    /**
     * Sum completed food orders revenue by merchant.
     *
     * @param string $merchantId
     * @param string $period
     * @return float
     */
    public function sumRevenueByMerchant(string $merchantId, string $period = 'today'): float;

    /**
     * Count completed food orders by merchant.
     *
     * @param string $merchantId
     * @param string $period
     * @return int
     */
    public function countCompletedOrdersByMerchant(string $merchantId, string $period = 'today'): int;

    /**
     * Get merchant revenue chart data.
     *
     * @param string $merchantId
     * @param string $period
     * @return array
     */
    public function getRevenueChartData(string $merchantId, string $period = 'today'): array;

    /**
     * Update food order status.
     *
     * @param string $orderId
     * @param int $status
     * @return bool
     */
    public function updateFoodOrderStatus(string $orderId, int $status): bool;

    /**
     * Reset cancellation request.
     *
     * @param string $orderId
     * @return bool
     */
    public function resetCancellationRequest(string $orderId): bool;

    /**
     * Lấy danh sách tất cả FoodOrder kèm thông tin khách hàng, cửa hàng và chuyến xe (Admin)
     *
     * @return \Illuminate\Support\Collection
     */
    public function listAllFoodOrdersForAdmin(): \Illuminate\Support\Collection;

    /**
     * Get paginated food orders for a merchant with optional status filtering.
     *
     * @param string $merchantId
     * @param array|null $statuses
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getMerchantOrders(string $merchantId, ?array $statuses = null, int $perPage = 20, int $page = 1): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}

