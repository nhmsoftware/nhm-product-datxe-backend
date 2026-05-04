<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\Interfaces\SubscriptionPackageRepositoryInterface;
use App\Modules\Finance\Model\SubscriptionPackage;
use Illuminate\Support\Collection;

final class SubscriptionPackageRepository extends BaseRepository implements SubscriptionPackageRepositoryInterface
{
    public function getModel(): string
    {
        return SubscriptionPackage::class;
    }

    public function getActivePackages(): Collection
    {
        return $this->model->where('is_active', true)->orderBy('price')->get();
    }
}
