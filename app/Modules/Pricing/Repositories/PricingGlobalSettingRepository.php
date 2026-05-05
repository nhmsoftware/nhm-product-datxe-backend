<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface;
use App\Modules\Pricing\Model\PricingGlobalSetting;

final class PricingGlobalSettingRepository extends BaseRepository implements PricingGlobalSettingRepositoryInterface
{
    public function getModel(): string
    {
        return PricingGlobalSetting::class;
    }

    public function getSettings(): ?PricingGlobalSetting
    {
        /** @var PricingGlobalSetting|null */
        return $this->model->first();
    }
}
