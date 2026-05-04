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
}
