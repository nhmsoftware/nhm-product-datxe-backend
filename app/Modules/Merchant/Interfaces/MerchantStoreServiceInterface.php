<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Services\ServiceReturn;

interface MerchantStoreServiceInterface
{
    /**
     * Get store information for the merchant.
     * UC-53 Manage Store
     */
    public function getStoreInfo(string $userId): ServiceReturn;

    /**
     * Update store status (Open/Close).
     * UC-46 Change status
     */
    public function updateStatus(string $userId, bool $isOpen): ServiceReturn;

    /**
     * Update store operating hours.
     * UC-45 Setup hours / UC-54 Set Opening Hours
     */
    public function updateOperatingHours(string $userId, string $openingTime, string $closingTime): ServiceReturn;

    /**
     * Update weekly operating hours schedule.
     * UC-54 Set Opening Hours
     */
    public function updateWeeklySchedule(string $userId, array $schedule): ServiceReturn;

    /**
     * Update store discount percentage (Renamed to Commission Rate).
     * UC-47 Configure discount
     */
    public function updateDiscount(string $userId, float $commissionRate): ServiceReturn;

    /**
     * Get available commission packages.
     * UC-56 Configure Commission
     */
    public function getCommissionPackages(): array;

    /**
     * Update store commission package.
     * UC-56 Configure Commission
     */
    public function updateCommissionPackage(string $userId, string $packageKey): ServiceReturn;

    /**
     * Get daily order statistics for the merchant.
     * UC-66 View total daily orders
     */
    public function getDailyOrderStats(string $userId): ServiceReturn;

    /**
     * Get daily revenue statistics for the merchant.
     * UC-67 View daily revenue
     */
    public function getDailyRevenueStats(string $userId): ServiceReturn;
}
