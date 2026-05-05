<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface PricingSurgeRuleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get active rules for a vehicle type.
     *
     * @param int $vehicleType
     * @return Collection
     */
    public function getActiveRules(int $vehicleType): Collection;

    /**
     * Get all rules for admin.
     *
     * @return Collection
     */
    public function getAllRules(): Collection;
}
