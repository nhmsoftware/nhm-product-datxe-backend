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
    public function findByUserId(string $userId): ?DriverProfile;

    /**
     * Cập nhật trạng thái trực tuyến của Driver.
     */
    public function updateOnlineStatus(
        string $driverId,
        bool $isOnline,
        ?float $currentLat = null,
        ?float $currentLng = null
    ): bool;

    /**
     * Cập nhật trạng thái của Driver (UC-32).
     * @param string $driverId
     * @param DriverStatus $status
     * @return bool
     */
    public function updateStatus(string $driverId, DriverStatus $status): bool;

    /**
     * Tăng số lần hủy trong ngày (UC-33).
     * @param string $driverId
     * @return int Số lần hủy mới
     */
    public function incrementCancelCount(string $driverId): int;

    /**
     * Thiết lập thời gian đóng băng nhận đơn (UC-33).
     * @param string $driverId
     * @param int $minutes
     * @return bool
     */
    public function setCooldown(string $driverId, int $minutes): bool;
}
