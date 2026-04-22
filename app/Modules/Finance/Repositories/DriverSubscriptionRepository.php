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

    public function getActiveByDriverId(int $driverId): ?DriverSubscription
    {
        /** @var DriverSubscription|null */
        return $this->model
            ->where('driver_id', $driverId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function hasActiveSubscription(int $driverId): bool
    {
        return $this->model
            ->where('driver_id', $driverId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }
}
