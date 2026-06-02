<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\Interfaces\DriverSubscriptionRepositoryInterface;
use App\Modules\Finance\Model\DriverSubscription;

final class DriverSubscriptionRepository extends BaseRepository implements DriverSubscriptionRepositoryInterface
{
    public function getModel(): string
    {
        return DriverSubscription::class;
    }

    public function getActiveByDriverId(string $driverId): ?DriverSubscription
    {
        /** @var DriverSubscription|null */
        return $this->getQuery()
            ->where('driver_id', $driverId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function hasActiveSubscription(string $driverId): bool
    {
        return $this->getQuery()
            ->where('driver_id', $driverId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * @inheritDoc
     */
    public function countTotalSubscriptionsByYear(int $year): int
    {
        return $this->getQuery()->whereYear('driver_subscriptions.created_at', $year)->count();
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptionsGroupedByPackage(int $year): \Illuminate\Support\Collection
    {
        return $this->getQuery()->whereYear('driver_subscriptions.created_at', $year)
            ->join('subscription_packages', 'driver_subscriptions.package_id', '=', 'subscription_packages.id')
            ->select([
                'subscription_packages.name',
                'subscription_packages.package_type',
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('subscription_packages.id', 'subscription_packages.name', 'subscription_packages.package_type')
            ->orderBy('subscription_packages.package_type')
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function countSubscriptionsByMonth(int $year, int $month): int
    {
        return $this->getQuery()->whereYear('driver_subscriptions.created_at', $year)
            ->whereMonth('driver_subscriptions.created_at', $month)
            ->count();
    }
}
