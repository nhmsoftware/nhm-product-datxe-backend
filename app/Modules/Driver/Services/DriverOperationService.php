<?php

declare(strict_types=1);

namespace App\Modules\Driver\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Driver\DTO\AcceptOrderDTO;
use App\Modules\Driver\DTO\CancelOrderDTO;
use App\Modules\Driver\DTO\RejectOrderDTO;
use App\Modules\Driver\DTO\ToggleOnlineStatusDTO;
use App\Modules\Driver\Events\RideAccepted;
use App\Modules\Driver\Events\RideCancelled;
use App\Modules\Driver\Events\RideRejected;
use App\Modules\Driver\Interfaces\DriverOperationServiceInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
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

            // UC-31 A3: Driver đang có chuyến (Check by User ID)
            $hasActiveRide = $this->rideRepository->hasActiveRideByDriver($driverProfile->user_id);
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

    /**
     * Xác nhận chuyến xe của tài xế.
     * @param AcceptOrderDTO $dto
     */
    public function acceptOrder(AcceptOrderDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra tài khoản User
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null, 'Tài khoản không tồn tại.', 404);
            $this->validate($user->isActive(), 'Tài khoản của bạn đã bị vô hiệu hóa.', 403);

            // 2. Kiểm tra Profile tài xế
            $driverProfile = $this->driverProfileRepository->findByUserId($dto->userId);
            $this->validate($driverProfile !== null, 'Hồ sơ tài xế không tồn tại.', 404);

            // UC-32: Preconditions & Constraints
            // Driver đang ở trạng thái Online
            $this->validate($driverProfile->is_online, 'Vui lòng bật Online để nhận đơn.', 403);

            // Driver không bị khóa/cooldown
            $this->validate($driverProfile->status !== DriverStatus::BANNED, 'Tài khoản đã bị khóa.', 403);
            if ($driverProfile->status === DriverStatus::COOLDOWN) {
                $until = $driverProfile->cooldown_until;
                if ($until && $until->isFuture()) {
                    $this->throw("Tài khoản đang trong thời gian tạm nghỉ.", 403);
                }
            }

            // A5: Driver đang bận (đã có đơn khác) - Check by User ID
            $hasActiveRide = $this->rideRepository->hasActiveRideByDriver($driverProfile->user_id);
            $this->validate(!$hasActiveRide, 'Bạn đang có đơn khác.', 422);

            // A7: GPS không hoạt động — Kiểm tra tọa độ từ DTO (đã validate ở FormRequest)
            $this->validate($dto->currentLat != 0 && $dto->currentLng != 0, 'Vui lòng bật GPS để nhận đơn.', 422);

            // 3. Kiểm tra thông tin chuyến xe
            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Đơn hàng không tồn tại.', 404);

            // A2, A3, A8: Kiểm tra trạng thái đơn
            // Đơn vẫn còn khả dụng & chưa được tài xế khác nhận
            $this->validate(
                $ride->status === RideStatus::PENDING && $ride->driver_id === null,
                'Đơn đã được nhận hoặc không còn khả dụng.',
                422
            );

            // 4. Ghi dữ liệu (Sử dụng execute với useTransaction: true)
            // Cập nhật Ride: status = ACCEPTED, driver_id (Referencing User ID)
            $rideUpdated = $this->rideRepository->acceptByDriver($ride->id, $driverProfile->user_id);
            $this->validate($rideUpdated, 'Không thể nhận đơn. Vui lòng thử lại.', 500);

            // Cập nhật Driver Status: status = BUSY
            $driverUpdated = $this->driverProfileRepository->updateStatus($driverProfile->id, DriverStatus::BUSY);
            $this->validate($driverUpdated, 'Lỗi hệ thống khi cập nhật trạng thái tài xế.', 500);

            // 5. Phát Domain Event để thông báo cho Customer qua Realtime
            event(new RideAccepted($ride->id, $driverProfile->id));

            return $ride->toArray();
        }, useTransaction: true);
    }


    /**
     * Từ chối chuyến xe của tài xế.
     * @param RejectOrderDTO $dto
     */
    public function rejectOrder(RejectOrderDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $driverProfile = $this->driverProfileRepository->findByUserId($dto->userId);
            $this->validate($driverProfile !== null, 'Hồ sơ tài xế không tồn tại.', 404);
            $this->validate($driverProfile->is_online, 'Vui lòng bật Online để thao tác.', 403);

            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Đơn hàng không tồn tại.', 404);
            $this->validate($ride->status === RideStatus::PENDING, 'Đơn không khả dụng để từ chối.', 422);

            // Từ chối (Check by User ID)
            $this->rideRepository->rejectByDriver($ride->id, $driverProfile->user_id);

            // Phát sự kiện để hệ thống biết tài xế từ chối đơn
            event(new RideRejected($ride->id, $driverProfile->id));

            return $this->success([], 'Đã từ chối đơn hàng.');
        }, useTransaction: true);
    }


    /**
     * Hủy chuyến xe của tài xế.
     * @param CancelOrderDTO $dto
     */
    public function cancelOrder(CancelOrderDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra tài xế
            $driverProfile = $this->driverProfileRepository->findByUserId($dto->userId);
            $this->validate($driverProfile !== null, 'Hồ sơ tài xế không tồn tại.', 404);

            // 2. Kiểm tra chuyến đi
            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Đơn hàng không tồn tại.', 404);

            // A5: Đơn đã bị hủy trước đó
            if ($ride->status === RideStatus::CANCELLED) {
                $this->throw('Đơn đã bị hủy trước đó.', 422);
            }

            // Kiểm tra tính sở hữu và trạng thái cho phép hủy (rides.driver_id is User ID)
            $this->validate($ride->driver_id === $driverProfile->user_id, 'Bạn không có quyền hủy đơn này.', 403);

            // A6: Không thể hủy ở trạng thái hiện tại (ví dụ: đã hoàn thành)
            $this->validate(
                in_array($ride->status, [RideStatus::ACCEPTED, RideStatus::IN_PROGRESS]),
                'Không thể hủy ở trạng thái hiện tại.',
                422
            );

            // 3. Thực hiện hủy đơn
            $this->rideRepository->cancelByDriver($ride->id, $dto->reason->value);

            // 4. Xử lý Penalty (A2, A3, A7)
            $penaltyMinutes = 0;

            // A2: Hủy khi đã đến điểm đón (Giả định ngưỡng 200m)
            if ($dto->currentLat !== null && $dto->currentLng !== null) {
                $distanceToPickup = $this->calculateDistance(
                    (float) $dto->currentLat,
                    (float) $dto->currentLng,
                    (float) $ride->pickup_lat,
                    (float) $ride->pickup_lng
                );
                if ($distanceToPickup <= 200) {
                    $penaltyMinutes = 30; // Phạt 30 phút nếu hủy sát điểm đón
                }
            }

            // A3/A7: Tỷ lệ hủy hoặc số lần hủy vượt ngưỡng
            $newCancelCount = $this->driverProfileRepository->incrementCancelCount($driverProfile->id);
            if ($newCancelCount >= 3) {
                $penaltyMinutes = max($penaltyMinutes, 60); // Phạt 60 phút nếu hủy > 3 lần/ngày
            }

            if ($penaltyMinutes > 0) {
                $this->driverProfileRepository->setCooldown($driverProfile->id, $penaltyMinutes);
            } else {
                // Nếu chưa bị phạt, chuyển trạng thái về ACTIVE (Online)
                $this->driverProfileRepository->updateStatus($driverProfile->id, DriverStatus::ACTIVE);
            }

            // 5. Phát sự kiện Customer
            event(new RideCancelled($ride->id, $driverProfile->id, $dto->reason->getLabel()));

            return $this->success([], 'Đã hủy chuyến đi thành công.');
        }, useTransaction: true);
    }

    /**
     * Helper tính khoảng cách Haversine (mét)
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
