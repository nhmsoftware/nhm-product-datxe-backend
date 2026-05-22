<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Pricing\Interfaces\PricingConfigHistoryRepositoryInterface;
use App\Modules\Pricing\Model\PricingConfigHistory;
use Illuminate\Support\Collection;

final class PricingConfigHistoryRepository extends BaseRepository implements PricingConfigHistoryRepositoryInterface
{
    public function getModel(): string
    {
        return PricingConfigHistory::class;
    }

    /**
     * @inheritDoc
     */
    public function getByVehicleType(int $vehicleType): Collection
    {
        return $this->getQuery()
            ->where('vehicle_type', $vehicleType)
            ->orderByDesc('created_at')
            ->get();
    }
}
