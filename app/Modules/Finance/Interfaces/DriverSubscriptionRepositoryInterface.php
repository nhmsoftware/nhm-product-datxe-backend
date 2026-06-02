<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Finance\Model\DriverSubscription;

interface DriverSubscriptionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get active subscription for a driver
     */
    public function getActiveByDriverId(string $driverId): ?DriverSubscription;

    /**
     * Check if driver already has an active subscription
     */
    public function hasActiveSubscription(string $driverId): bool;

    /**
     * Đếm tổng số đăng ký gói trong năm
     */
    public function countTotalSubscriptionsByYear(int $year): int;

    /**
     * Lấy số lượng đăng ký nhóm theo từng loại gói trong năm
     */
    public function getSubscriptionsGroupedByPackage(int $year): \Illuminate\Support\Collection;

    /**
     * Đếm số đăng ký gói trong tháng cụ thể của một năm
     */
    public function countSubscriptionsByMonth(int $year, int $month): int;
}
