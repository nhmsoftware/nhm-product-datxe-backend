<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\User\Model\DriverProfile;
use App\Modules\User\Model\Enums\DriverStatus;

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

    /**
     * Cập nhật trạng thái của Driver (UC-32).
     * @param int $driverId
     * @param DriverStatus $status
     * @return bool
     */
    public function updateStatus(int $driverId, DriverStatus $status): bool;

    /**
     * Tăng số lần hủy trong ngày (UC-33).
     * @param int $driverId
     * @return int Số lần hủy mới
     */
    public function incrementCancelCount(int $driverId): int;

    /**
     * Thiết lập thời gian đóng băng nhận đơn (UC-33).
     * @param int $driverId
     * @param int $minutes
     * @return bool
     */
    public function setCooldown(int $driverId, int $minutes): bool;
}
