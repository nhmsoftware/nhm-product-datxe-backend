<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Model\DriverProfile;
use App\Modules\User\Model\Enums\DriverStatus;

final class DriverProfileRepository extends BaseRepository implements DriverProfileRepositoryInterface
{
    public function getModel(): string
    {
        return DriverProfile::class;
    }

    public function findByUserId(string $userId): ?DriverProfile
    {
        /** @var DriverProfile|null */
        return $this->model->where('user_id', $userId)->first();
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

        return (bool) $this->model->where('id', $driverId)->update($data);
    }

    /**
     * Cập nhật trạng thái của Driver (UC-32).
     */
    public function updateStatus(string $driverId, DriverStatus $status): bool
    {
        return (bool) $this->model->where('id', $driverId)->update([
            'status' => $status->value,
        ]);
    }

    /**
     * Tăng số lần hủy trong ngày (UC-33).
     */
    public function incrementCancelCount(string $driverId): int
    {
        $profile = $this->model->find($driverId);
        if (!$profile) {
            return 0;
        }

        $profile->increment('cancel_count_today');
        return (int) $profile->cancel_count_today;
    }

    /**
     * Thiết lập thời gian đóng băng nhận đơn (UC-33).
     */
    public function setCooldown(string $driverId, int $minutes): bool
    {
        return (bool) $this->model->where('id', $driverId)->update([
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

        $query = $this->model
            ->whereIn('user_id', $userIds)
            ->where('is_online', true)
            ->where('status', DriverStatus::ACTIVE->value)
            ->where('vehicle_type', $vehicleType);

        if ($groupType !== null) {
            $query->where('driver_group_type', $groupType);
        }

        return $query->get();
    }
}
