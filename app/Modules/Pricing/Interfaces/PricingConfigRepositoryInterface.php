<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Pricing\Model\PricingConfig;

interface PricingConfigRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find config by vehicle type.
     * UC-91 Configure Pricing
     *
     * @param int $vehicleType
     * @return PricingConfig|null
     */
    public function findActiveByVehicleTypeId(int $vehicleTypeId): ?PricingConfig;

    public function findLatestByVehicleTypeId(int $vehicleTypeId): ?PricingConfig;

    /**
     * Get all configs.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllConfigs(): \Illuminate\Database\Eloquent\Collection;

    public function getAllLatestConfigs(): \Illuminate\Database\Eloquent\Collection;
}
