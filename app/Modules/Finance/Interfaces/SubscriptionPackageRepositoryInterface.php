<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Support\Collection;

interface SubscriptionPackageRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get all active subscription packages
     */
    public function getActivePackages(): Collection;
}
