<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Model\DriverProfile;
use App\Modules\User\Model\Enums\DriverStatus;
use Illuminate\Support\Facades\DB;

final class DriverProfileRepository extends BaseRepository implements DriverProfileRepositoryInterface
{
    public function getModel(): string
    {
        return DriverProfile::class;
    }

    public function findByUserId(string $userId): ?DriverProfile
    {
        /** @var DriverProfile|null */
        return $this->getQuery()->where('user_id', $userId)->first();
    }

    /**
     * Cập nhật trạng thái trực tuyến của Driver.
     */
    public function updateOnlineStatus(
        string $driverId,
        bool $isOnline,
        ?float $currentLat = null,
        ?float $currentLng = null
    ): bool {
        $data = ['is_online' => $isOnline];

        if ($isOnline && $currentLat !== null && $currentLng !== null) {
            $data['current_lat'] = $currentLat;
            $data['current_lng'] = $currentLng;
        }

        return (bool) $this->getQuery()->where('id', $driverId)->update($data);
    }

    /**
     * Cập nhật trạng thái của Driver (UC-32).
     */
    public function updateStatus(string $driverId, DriverStatus $status): bool
    {
        return (bool) $this->getQuery()->where('id', $driverId)->update([
            'status' => $status->value,
        ]);
    }

    /**
     * Cập nhật số lần hủy trong ngày (UC-33).
     */
    public function updateCancelCount(string $driverId, int $count): bool
    {
        return (bool) $this->getQuery()->where('id', $driverId)->update([
            'cancel_count_today' => $count,
        ]);
    }

    /**
     * Thiết lập thời gian đóng băng nhận đơn (UC-33).
     */
    public function setCooldown(string $driverId, int $minutes): bool
    {
        return (bool) $this->getQuery()->where('id', $driverId)->update([
            'status'         => DriverStatus::COOLDOWN->value,
            'cooldown_until' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function findEligibleDrivers(array $userIds, int $vehicleType, ?int $groupType = null): \Illuminate\Support\Collection
    {
        if (empty($userIds)) {
            return collect();
        }

        $query = $this->getQuery()
            ->whereIn('user_id', $userIds)
            ->where('is_online', true)
            ->where(function ($q) {
                $q->where('status', DriverStatus::ACTIVE->value)
                  ->orWhere(function ($q2) {
                      $q2->where('status', DriverStatus::COOLDOWN->value)
                         ->where('cooldown_until', '<=', now());
                  });
            })
            ->where('vehicle_type', $vehicleType);

        if ($groupType !== null) {
            $query->where('driver_group_type', $groupType);
        }

        return $query->get();
    }

    /**
     * Đếm số lượng tài xế đang hoạt động (Online và Active)
     */
    public function countActiveDrivers(): int
    {
        return $this->getQuery()
            ->where('is_online', true)
            ->where(function ($q) {
                $q->where('status', DriverStatus::ACTIVE->value)
                  ->orWhere(function ($q2) {
                      $q2->where('status', DriverStatus::COOLDOWN->value)
                         ->where('cooldown_until', '<=', now());
                  });
            })
            ->count();
    }

    /**
     * Cập nhật vị trí hiện tại của tài xế.
     */
    public function updateLocation(string $driverId, float $lat, float $lng): bool
    {
        return (bool) $this->getQuery()->where('id', $driverId)->update([
            'current_lat' => $lat,
            'current_lng' => $lng,
        ]);
    }

    public function countTotalDrivers(): int
    {
        return $this->getQuery()->count();
    }

    public function countByGroupType(\App\Modules\User\Model\Enums\DriverGroupType $type): int
    {
        return $this->getQuery()->where('driver_group_type', $type->value)->count();
    }

    public function countByStatus(DriverStatus $status): int
    {
        return $this->getQuery()->where('status', $status->value)->count();
    }
}
