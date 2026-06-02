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

    /**
     * UC-46: Lấy danh sách gói đang hoạt động (Driver)
     */
    public function getActivePackages(): Collection
    {
        return $this->getQuery()->where('is_active', true)->orderBy('price')->get();
    }

    /**
     * UC-118: Lấy tất cả gói kể cả vô hiệu (Admin)
     */
    public function getAllPackages(): Collection
    {
        return $this->getQuery()->orderBy('price')->get();
    }

    /**
     * UC-118: Kiểm tra tên gói trùng (A4)
     */
    public function findByName(string $name, ?string $excludeId = null): ?SubscriptionPackage
    {
        /** @var SubscriptionPackage|null */
        $query = $this->getQuery()->where('name', $name);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * UC-118: Kiểm tra có tài xế đang dùng gói này không (A5)
     */
    public function hasActiveDriverSubscriptions(string $packageId): bool
    {
        return \App\Modules\Finance\Model\DriverSubscription::where('package_id', $packageId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }
}
