<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Pricing\Interfaces\PricingConfigRepositoryInterface;
use App\Modules\Pricing\Model\PricingConfig;
use Illuminate\Database\Eloquent\Collection;

final class PricingConfigRepository extends BaseRepository implements PricingConfigRepositoryInterface
{
    public function getModel(): string
    {
        return PricingConfig::class;
    }

    public function findByVehicleType(int $vehicleType): ?PricingConfig
    {
        /** @var PricingConfig|null */
        return $this->getQuery()->where('vehicle_type', $vehicleType)->first();
    }

    public function getAllConfigs(): Collection
    {
        return $this->getQuery()->all();
    }
}
