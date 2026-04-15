<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Ride\Model\Enums\RideCallStatus;
use App\Modules\Ride\Model\Enums\RideChatSenderType;
use App\Modules\Ride\Model\RideCallLog;

interface RideCallLogRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tạo log cuộc gọi giữa customer và driver cho một ride (UC-14 bước 9).
     *
     * @param int $rideId
     * @param int $callerId
     * @param int $calleeId
     * @param RideChatSenderType $callerType
     * @param RideCallStatus $status
     * @return RideCallLog
     */
    public function createRideCallAttempt(
        int $rideId,
        int $callerId,
        int $calleeId,
        RideChatSenderType $callerType,
        RideCallStatus $status
    ): RideCallLog;

    /**
     * Tìm một cuộc gọi theo ride và callId để cập nhật trạng thái (UC-14 A3, A4).
     *
     * @param int $rideId
     * @param int $callId
     * @return RideCallLog|null
     */
    public function findRideCallByIdAndRide(int $rideId, int $callId): ?RideCallLog;

    /**
     * Cập nhật trạng thái cuộc gọi và lý do lỗi nếu có (UC-14 A3, A4).
     *
     * @param int $callId
     * @param RideCallStatus $status
     * @param string|null $failureReason
     * @return bool
     */
    public function updateRideCallStatus(int $callId, RideCallStatus $status, ?string $failureReason = null): bool;
}
