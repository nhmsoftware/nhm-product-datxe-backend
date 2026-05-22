<?php

declare(strict_types=1);

namespace App\Modules\Order\Interfaces;

use App\Core\Services\ServiceReturn;

interface AdminOrderServiceInterface
{
    /**
     * Lấy danh sách đơn hàng dịch vụ (Admin)
     */
    public function getServiceOrders(): ServiceReturn;

    /**
     * Chỉ định tài xế cho đơn hàng dịch vụ (Admin)
     */
    public function assignDriver(string $orderId, string $driverId): ServiceReturn;

    /**
     * Đẩy đơn hàng dịch vụ ra pool (Admin)
     */
    public function pushToPool(array $orderIds): ServiceReturn;
}
