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

    public function findActiveByVehicleTypeId(int $vehicleTypeId): ?PricingConfig
    {
        /** @var PricingConfig|null */
        return $this->getQuery()
            ->where('vehicle_type_id', $vehicleTypeId)
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();
    }

    public function findLatestByVehicleTypeId(int $vehicleTypeId): ?PricingConfig
    {
        /** @var PricingConfig|null */
        return $this->getQuery()
            ->where('vehicle_type_id', $vehicleTypeId)
            ->latest('updated_at')
            ->first();
    }

    public function getAllConfigs(): Collection
    {
        return $this->getQuery()->with('vehicleTypeRef')->latest('updated_at')->get();
    }

    public function getAllLatestConfigs(): Collection
    {
        return $this->getAllConfigs()
            ->unique('vehicle_type_id')
            ->values();
    }
}
