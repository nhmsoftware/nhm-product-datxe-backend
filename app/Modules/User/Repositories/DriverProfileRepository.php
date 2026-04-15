<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Model\DriverProfile;

final class DriverProfileRepository extends BaseRepository implements DriverProfileRepositoryInterface
{
    public function getModel(): string
    {
        return DriverProfile::class;
    }

    /**
     * Tìm DriverProfile của một user.
     */
    public function findByUserId(int $userId): ?DriverProfile
    {
        /** @var DriverProfile|null */
        return $this->model->where('user_id', $userId)->first();
    }

    /**
     * Cập nhật trạng thái trực tuyến của Driver.
     */
    public function updateOnlineStatus(
        int $driverId,
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
}
