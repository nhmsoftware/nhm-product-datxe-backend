<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Support\Collection;

interface PricingConfigHistoryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get pricing config history by vehicle type ordered by creation date descending.
     *
     * @param int $vehicleType
     * @return Collection
     */
    public function getByVehicleTypeId(int $vehicleTypeId): Collection;
}
