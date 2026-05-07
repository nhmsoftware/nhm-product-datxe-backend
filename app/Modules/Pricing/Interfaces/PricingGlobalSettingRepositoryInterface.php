<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Pricing\Model\PricingGlobalSetting;

interface PricingGlobalSettingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get the current global settings (only one row expected).
     * UC-91 Configure Pricing
     *
     * @return PricingGlobalSetting|null
     */
    public function getSettings(): ?PricingGlobalSetting;
}
