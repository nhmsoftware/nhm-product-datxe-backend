<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Pricing\Interfaces\PricingSurgeRuleRepositoryInterface;
use App\Modules\Pricing\Model\PricingSurgeRule;
use Illuminate\Database\Eloquent\Collection;

final class PricingSurgeRuleRepository extends BaseRepository implements PricingSurgeRuleRepositoryInterface
{
    public function getModel(): string
    {
        return PricingSurgeRule::class;
    }

    public function getActiveRules(int $vehicleType): Collection
    {
        return $this->model
            ->where('vehicle_type', $vehicleType)
            ->where('is_active', true)
            ->get();
    }

    public function getAllRules(): Collection
    {
        return $this->model->latest()->get();
    }
}
