<?php

declare(strict_types=1);

namespace App\Modules\Driver\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Driver\DTO\ToggleOnlineStatusDTO;
use App\Modules\Driver\Interfaces\DriverOperationServiceInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\DriverStatus;

final class DriverOperationService extends BaseService implements DriverOperationServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly DriverProfileRepositoryInterface $driverProfileRepository,
        private readonly RideRepositoryInterface $rideRepository,
    ) {}

    public function toggleOnlineStatus(ToggleOnlineStatusDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null, 'Tài khoản không tồn tại.', 404);
            $this->validate($user->isActive(), 'Tài khoản của bạn đã bị vô hiệu hóa.', 403);

            $driverProfile = $this->driverProfileRepository->findByUserId($dto->userId);

            // UC-31 A1: Tài khoản chưa được duyệt
            $this->validate($driverProfile !== null, 'Tài khoản tài xế chưa được kích hoạt.', 403);
            
            // UC-31 A5: Driver bị khóa tài khoản
            if ($driverProfile->status === DriverStatus::BANNED) {
                $this->throw('Tài khoản tài xế của bạn đã bị khóa vĩnh viễn.', 403);
            }

            if ($driverProfile->status === DriverStatus::COOLDOWN) {
                $until = $driverProfile->cooldown_until;
                if ($until && $until->isFuture()) {
                    $this->throw("Tài khoản đang trong thời gian tạm nghỉ đến " . $until->format('H:i d/m/Y'), 403);
                }
            }

            // UC-31 A3: Driver đang có chuyến
            $hasActiveRide = $this->rideRepository->hasActiveRideByDriver($driverProfile->id);
            $this->validate(
                !$hasActiveRide,
                'Không thể cập nhật trạng thái khi đang có chuyến.',
                422
            );

            // Thực hiện cập nhật
            $this->driverProfileRepository->updateOnlineStatus(
                $driverProfile->id,
                $dto->isOnline,
                $dto->currentLat,
                $dto->currentLng
            );

            $statusText = $dto->isOnline ? 'Online' : 'Offline';

            return $this->success(
                data: [
                    'is_online'   => $dto->isOnline,
                    'current_lat' => $dto->currentLat,
                    'current_lng' => $dto->currentLng,
                ],
                message: "Đã cập nhật trạng thái thành {$statusText}."
            );
        }, useTransaction: true);
    }
}
