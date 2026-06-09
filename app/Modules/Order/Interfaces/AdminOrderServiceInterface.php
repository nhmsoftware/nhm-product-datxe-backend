<?php

declare(strict_types=1);

namespace App\Modules\Order\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Order\DTO\AdminCreateFoodOrderDTO;
use App\Modules\Order\DTO\AdminUpdateFoodOrderDTO;

interface AdminOrderServiceInterface
{
    /**
     * Lấy danh sách đơn hàng dịch vụ (Admin)
     */
    public function getServiceOrders(): ServiceReturn;

    /**
     * Lấy chi tiết đơn hàng dịch vụ (Admin)
     */
    public function getServiceOrderDetail(string $orderId): ServiceReturn;

    /**
     * Tạo đơn đồ ăn thủ công (Admin)
     */
    public function createFoodOrder(AdminCreateFoodOrderDTO $dto): ServiceReturn;

    /**
     * Cập nhật đơn đồ ăn (Admin)
     */
    public function updateFoodOrder(AdminUpdateFoodOrderDTO $dto): ServiceReturn;

    /**
     * Hủy đơn đồ ăn (Admin)
     */
    public function cancelFoodOrder(string $orderId, ?string $reason = null): ServiceReturn;

    /**
     * Chỉ định tài xế cho đơn hàng dịch vụ (Admin)
     */
    public function assignDriver(string $orderId, string $driverId): ServiceReturn;

    /**
     * Đẩy đơn hàng dịch vụ ra pool (Admin)
     */
    public function pushToPool(array $orderIds): ServiceReturn;
}
