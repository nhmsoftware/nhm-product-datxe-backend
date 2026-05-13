<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Finance\Model\SubscriptionPackage;
use Illuminate\Support\Collection;

interface SubscriptionPackageRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get all active subscription packages (UC-46 - Driver)
     */
    public function getActivePackages(): Collection;

    /**
     * Get all packages including inactive (UC-118 - Admin)
     */
    public function getAllPackages(): Collection;

    /**
     * Find package by name for duplicate check (UC-118 - A4)
     */
    public function findByName(string $name, ?string $excludeId = null): ?SubscriptionPackage;

    /**
     * Check if any active driver subscription uses this package (UC-118 - A5)
     */
    public function hasActiveDriverSubscriptions(string $packageId): bool;
}
