<?php

declare(strict_types=1);

namespace App\Modules\Operation\Interfaces;

/**
 * Interface Repository quản lý việc cập nhật tọa độ vào các bảng profile.
 */
interface LocationRepositoryInterface
{
    /**
     * Cập nhật tọa độ cho Driver.
     */
    public function updateDriverLocation(string $userId, float $lat, float $lng): bool;

    /**
     * Cập nhật tọa độ cho Customer.
     */
    public function updateCustomerLocation(string $userId, float $lat, float $lng): bool;

    /**
     * Lấy tọa độ Driver mới nhất từ Redis.
     */
    public function getDriverLocation(string $userId): ?array;

    /**
     * Lấy tọa độ Customer mới nhất từ Redis.
     */
    public function getCustomerLocation(string $userId): ?array;
}
