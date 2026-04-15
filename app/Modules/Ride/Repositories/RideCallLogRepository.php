<?php

declare(strict_types=1);

namespace App\Modules\Ride\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Ride\Interfaces\RideCallLogRepositoryInterface;
use App\Modules\Ride\Model\Enums\RideCallStatus;
use App\Modules\Ride\Model\Enums\RideChatSenderType;
use App\Modules\Ride\Model\RideCallLog;

final class RideCallLogRepository extends BaseRepository implements RideCallLogRepositoryInterface
{
    public function getModel(): string
    {
        return RideCallLog::class;
    }

    public function createRideCallAttempt(
        int $rideId,
        int $callerId,
        int $calleeId,
        RideChatSenderType $callerType,
        RideCallStatus $status
    ): RideCallLog {
        /** @var RideCallLog $callLog */
        $callLog = $this->create([
            'ride_id' => $rideId,
            'caller_id' => $callerId,
            'callee_id' => $calleeId,
            'caller_type' => $callerType->value,
            'status' => $status->value,
        ]);

        return $callLog;
    }

    public function findRideCallByIdAndRide(int $rideId, int $callId): ?RideCallLog
    {
        /** @var RideCallLog|null */
        return $this->model
            ->where('id', $callId)
            ->where('ride_id', $rideId)
            ->first();
    }

    public function updateRideCallStatus(int $callId, RideCallStatus $status, ?string $failureReason = null): bool
    {
        return (bool) $this->model
            ->where('id', $callId)
            ->update([
                'status' => $status->value,
                'failure_reason' => $failureReason,
            ]);
    }
}
