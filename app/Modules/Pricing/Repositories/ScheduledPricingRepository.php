<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Pricing\Interfaces\ScheduledPricingRepositoryInterface;
use App\Modules\Pricing\Model\ScheduledPricingConfig;

final class ScheduledPricingRepository extends BaseRepository implements ScheduledPricingRepositoryInterface
{
    public function getModel(): string
    {
        return ScheduledPricingConfig::class;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentConfig(): ?ScheduledPricingConfig
    {
        /** @var ScheduledPricingConfig|null */
        return $this->model->where('is_active', true)->first();
    }
}
