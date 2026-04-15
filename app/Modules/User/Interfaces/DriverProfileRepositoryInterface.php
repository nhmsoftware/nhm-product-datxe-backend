<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\User\Model\DriverProfile;

interface DriverProfileRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm DriverProfile của một user.
     */
    public function findByUserId(int $userId): ?DriverProfile;

    /**
     * Cập nhật trạng thái trực tuyến của Driver.
     */
    public function updateOnlineStatus(
        int $driverId,
        bool $isOnline,
        ?float $currentLat = null,
        ?float $currentLng = null
    ): bool;
}
