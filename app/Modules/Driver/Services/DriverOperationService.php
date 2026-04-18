<?php

declare(strict_types=1);

namespace App\Modules\Driver\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Driver\DTO\AcceptOrderDTO;
use App\Modules\Driver\DTO\CancelOrderDTO;
use App\Modules\Driver\DTO\RejectOrderDTO;
use App\Modules\Driver\DTO\PickupRideDTO;
use App\Modules\Driver\DTO\ToggleOnlineStatusDTO;
use App\Modules\Driver\Events\DriverArrivedAtPickup;
use App\Modules\Driver\Events\RideAccepted;
use App\Modules\Driver\Events\RideCancelled;
use App\Modules\Driver\Events\RidePickedUp;
use App\Modules\Driver\Events\RideRejected;
use App\Modules\Driver\Events\RideStarted;
use App\Modules\Driver\Events\RideCompleted;
use App\Modules\Driver\DTO\StartRideDTO;
use App\Modules\Driver\DTO\CompleteRideDTO;
use App\Modules\Driver\Interfaces\DriverOperationServiceInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\DriverStatus;

/**
 * DriverOperationService
 *
 * Service này chịu trách nhiệm điều phối toàn bộ vòng đời vận hành của Tài xế:
 * từ việc Bật/Tắt trạng thái hoạt động, Nhận/Từ chối chuyến đến việc Xác nhận đón khách.
 *
 * Tuân thủ nghiêm ngặt kiến trúc Modular DDD:
 * - Dữ liệu đầu vào chuẩn hóa qua DTO.
 * - Mọi thao tác ghi DB được bọc trong giao dịch (useTransaction: true).
 * - Giao tiếp giữa các module qua Domain Events và Redis Realtime.
 */
final class DriverOperationService extends BaseService implements DriverOperationServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly DriverProfileRepositoryInterface $driverProfileRepository,
        private readonly RideRepositoryInterface $rideRepository,
    ) {}

    /**
     * Thông báo đã đến điểm đón (A1 UC-36).
     *
     * Logic:
     * 1. Kiểm tra tính hợp lệ của chuyến xe và quyền sở hữu của tài xế.
     * 2. Tính toán khoảng cách thực tế giữa Tài xế và Điểm đón khách (Haversine).
     * 3. Chỉ cho phép thông báo "Đã đến" nếu tài xế nằm trong bán kính 200m.
     * 4. Phát sự kiện DriverArrivedAtPickup để Realtime service báo cho khách hàng.
     */
    public function notifyArrived(PickupRideDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Chuyến xe không tồn tại.', 404);
            $this->validate($ride->driver_id === $dto->userId, 'Bạn không phải tài xế của chuyến xe này.', 403);
            $this->validate($ride->status === RideStatus::ACCEPTED, 'Trạng thái chuyến xe không hợp lệ.', 422);

            $driverProfile = $this->driverProfileRepository->findByUserId($dto->userId);
            $this->validate($driverProfile !== null, 'Hồ sơ tài xế không tồn tại.', 404);

            // Kiểm tra vị trí GPS (Ngưỡng cho phép 200m)
            $distance = $this->calculateDistance(
                (float) $dto->lat,
                (float) $dto->lng,
                (float) $ride->pickup_lat,
                (float) $ride->pickup_lng
            );

            if ($distance > 200) {
                Log::debug('Distance check failed for notifyArrived', [
                    'distance' => $distance,
                    'ride_id' => $dto->rideId,
                    'user_id' => $dto->userId
                ]);
                $this->throw('Bạn chưa đủ gần điểm đón để thông báo đã đến (Bán kính 200m).', 422);
            }

            // Gửi sự kiện Domain Event với driverProfile->id để listener tìm thấy
            event(new DriverArrivedAtPickup($ride->id, $driverProfile->id));

            return $this->success([], 'Đã gửi thông báo đến khách hàng.');
        });
    }

    /**
     * Xác nhận đã đón khách/lấy hàng thành công (UC-36).
     *
     * Logic:
     * 1. Kiểm tra hồ sơ tài xế và trạng thái chuyến xe.
     * 2. Xác thực vị trí GPS hiện tại (bán kính 200m) để chống việc xác nhận khống.
     * 3. Cập nhật trạng thái chuyến xe sang PICKED_UP (Giá trị 7).
     * 4. Phát sự kiện RidePickedUp để hệ thống chuyển trạng thái trên App của Khách hàng.
     */
    public function pickupRide(PickupRideDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra tài khoản và Profile tài xế
            $driverProfile = $this->driverProfileRepository->findByUserId($dto->userId);
            $this->validate($driverProfile !== null, 'Hồ sơ tài xế không tồn tại.', 404);

            // 2. Kiểm tra thông tin chuyến xe
            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Chuyến xe không tồn tại.', 404);

            // Kiểm tra tính sở hữu
            $this->validate($ride->driver_id === $dto->userId, 'Bạn không phải tài xế của chuyến xe này.', 403);

            // Phải là trạng thái ACCEPTED mới được đón khách
            $this->validate($ride->status === RideStatus::ACCEPTED, 'Trạng thái chuyến xe không hợp lệ để xác nhận đón khách.', 422);

            // 3. Kiểm tra vị trí GPS (Bắt buộc gần điểm đón)
            $distance = $this->calculateDistance(
                (float) $dto->lat,
                (float) $dto->lng,
                (float) $ride->pickup_lat,
                (float) $ride->pickup_lng
            );

            if ($distance > 200) {
                Log::debug('Distance check failed for pickupRide', [
                    'distance' => $distance,
                    'ride_id' => $dto->rideId,
                    'user_id' => $dto->userId
                ]);
                $this->throw('Vị trí hiện tại của bạn cách điểm đón quá xa. Vui lòng di chuyển đến đúng vị trí.', 422);
            }

            // 4. Thực hiện cập nhật trạng thái trong Persistence Layer
            $updated = $this->rideRepository->pickup($ride->id);
            $this->validate($updated, 'Không thể cập nhật trạng thái. Vui lòng thử lại.', 500);

            // 5. Phát Domain Event gởi sang Redis Communication
            event(new RidePickedUp($ride->id, $driverProfile->id));

            return $this->success(
                data: ['ride_id' => $ride->id, 'status' => RideStatus::PICKED_UP->value],
                message: 'Xác nhận đón khách thành công.'
            );
        }, useTransaction: true);
    }

    /**
     * Tài xế bắt đầu thực hiện chuyến đi (UC-35 Start Trip).
     */
    public function startRide(StartRideDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Chuyến xe không tồn tại.', 404);
            $this->validate($ride->driver_id === $dto->userId, 'Bạn không phải tài xế của chuyến xe này.', 403);

            // Phải là trạng thái PICKED_UP mới được bắt đầu chuyến
            $this->validate($ride->status === RideStatus::PICKED_UP, 'Trạng thái chuyến xe không hợp lệ để bắt đầu.', 422);

            // 3. Kiểm tra vị trí GPS (Bán kính 200m so với điểm đón)
            $distance = $this->calculateDistance(
                (float) $dto->currentLat,
                (float) $dto->currentLng,
                (float) $ride->pickup_lat,
                (float) $ride->pickup_lng
            );

            if ($distance > 200) {
                $this->throw('Bạn chưa đủ gần điểm đón để bắt đầu chuyến đi.', 422);
            }

            $updated = $this->rideRepository->startTrip($dto->rideId);
            $this->validate($updated, 'Không thể cập nhật trạng thái. Vui lòng thử lại.', 500);

            event(new RideStarted($dto->rideId, $dto->userId));

            return $this->success(
                data: ['ride_id' => $ride->id, 'status' => RideStatus::IN_PROGRESS->value],
                message: 'Chuyến đi đã bắt đầu.'
            );
        }, useTransaction: true);
    }

    /**
     * Tài xế hoàn thành chuyến đi (UC-40 Complete Trip).
     */
    public function completeRide(CompleteRideDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Chuyến xe không tồn tại.', 404);
            $this->validate($ride->driver_id === $dto->userId, 'Bạn không phải tài xế của chuyến xe này.', 403);

            // Phải là trạng thái IN_PROGRESS mới được hoàn thành
            $this->validate($ride->status === RideStatus::IN_PROGRESS, 'Trạng thái chuyến xe không hợp lệ để hoàn thành.', 422);

            // 3. Kiểm tra vị trí GPS (Bán kính 200m so với điểm đến)
            $distance = $this->calculateDistance(
                (float) $dto->currentLat,
                (float) $dto->currentLng,
                (float) $ride->destination_lat,
                (float) $ride->destination_lng
            );

            if ($distance > 200) {
                $this->throw('Bạn chưa đủ gần điểm đến để hoàn thành chuyến đi.', 422);
            }

            // TODO: Tính toán giá cước cuối cùng nếu cần. Hiện tại dùng giá đã chốt.
            $finalFare = (float) $ride->total_price;

            $updated = $this->rideRepository->completeTrip($dto->rideId, $finalFare);
            $this->validate($updated, 'Không thể hoàn thành chuyến xe. Vui lòng thử lại.', 500);

            // Cập nhật trạng thái tài xế sang Sẵn sàng (ACTIVE)
            $driverProfile = $this->driverProfileRepository->findByUserId($dto->userId);
            if ($driverProfile) {
                $this->driverProfileRepository->updateStatus($driverProfile->id, DriverStatus::ACTIVE);
            }

            event(new RideCompleted($dto->rideId, $dto->userId, $finalFare));

            return $this->success(
                data: ['ride_id' => $ride->id, 'status' => RideStatus::COMPLETED->value, 'final_fare' => $finalFare],
                message: 'Chuyến đi đã hoàn thành.'
            );
        }, useTransaction: true);
    }

    /**
     * Bật/Tắt trạng thái hoạt động của tài xế (UC-31 Online Status).
     *
     * Cho phép tài xế chuyển sang Offline kể cả khi đang có chuyến đi (Go offline after this trip).
     * Tuy nhiên, không thể Nhận thêm đơn mới nếu đang ở trạng thái Offline.
     */
    public function toggleOnlineStatus(ToggleOnlineStatusDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null, 'Tài khoản không tồn tại.', 404);
            $this->validate($user->isActive(), 'Tài khoản của bạn đã bị vô hiệu hóa.', 403);

            $driverProfile = $this->driverProfileRepository->findByUserId($dto->userId);
            $this->validate($driverProfile !== null, 'Tài khoản tài xế chưa được kích hoạt.', 403);

            // Kiểm tra các ràng buộc trạng thái: Bị khóa hoặc đang trong thời gian nghỉ
            if ($driverProfile->status === DriverStatus::BANNED) {
                $this->throw('Tài khoản tài xế của bạn đã bị khóa vĩnh viễn.', 403);
            }

            if ($driverProfile->status === DriverStatus::COOLDOWN) {
                $until = $driverProfile->cooldown_until;
                if ($until && $until->isFuture()) {
                    $this->throw("Tài khoản đang trong thời gian tạm nghỉ đến " . $until->format('H:i d/m/Y'), 403);
                }
            }

            // Cập nhật trạng thái hoạt động thông qua Repository
            $this->driverProfileRepository->updateOnlineStatus(
                $driverProfile->id,
                $dto->isOnline,
                $dto->currentLat,
                $dto->currentLng
            );

            $statusText = $dto->isOnline ? 'Trực tuyến' : 'Ngoại tuyến';

            return $this->success(
                data: [
                    'is_online'   => $dto->isOnline,
                    'current_lat' => $dto->currentLat,
                    'current_lng' => $dto->currentLng,
                ],
                message: "Bạn đang ở trạng thái {$statusText}."
            );
        }, useTransaction: true);
    }

    /**
     * Tài xế nhận chuyến đi (UC-32 Accept Order).
     *
     * Logic:
     * 1. Kiểm tra tài xế có đang Online và đáp ứng các tiêu chuẩn sức khỏe/pháp lý không.
     * 2. Kiểm tra tài xế có đang bận với chuyến đi khác không (A5).
     * 3. Kiểm tra đơn hàng vẫn đang ở trạng thái PENDING và chưa có ai nhận.
     * 4. Gán driver_id vào chuyến xe và chuyển trạng thái sang ACCEPTED.
     * 5. Đánh dấu tài xế ở trạng thái BUSY.
     */
    public function acceptOrder(AcceptOrderDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra tài khoản và Profile
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null, 'Tài khoản không tồn tại.', 404);
            $this->validate($user->isActive(), 'Tài khoản đã bị vô hiệu hóa.', 403);

            $driverProfile = $this->driverProfileRepository->findByUserId($dto->userId);
            $this->validate($driverProfile !== null, 'Hồ sơ tài xế không tồn tại.', 404);

            // Ràng buộc Online
            $this->validate($driverProfile->is_online, 'Vui lòng bật Trực tuyến để nhận đơn.', 403);
            $this->validate($driverProfile->status !== DriverStatus::BANNED, 'Tài khoản đã bị khóa.', 403);

            if ($driverProfile->status === DriverStatus::COOLDOWN) {
                $until = $driverProfile->cooldown_until;
                if ($until && $until->isFuture()) {
                    $this->throw("Tài khoản đang trong thời gian tạm nghỉ.", 403);
                }
            }

            // Kiểm tra xem tài xế đã có chuyến đi nào đang diễn ra chưa
            $hasActiveRide = $this->rideRepository->hasActiveRideByDriver($driverProfile->user_id);
            $this->validate(!$hasActiveRide, 'Bạn đang có một chuyến đi khác chưa hoàn thành.', 422);

            // Kiểm tra xem tài xế đã từng từ chối hoặc hủy chuyến xe này chưa
            $isRejected = $this->rideRepository->isRejectedByDriver($dto->rideId, $driverProfile->user_id);
            $this->validate(!$isRejected, 'Bạn đã từ chối hoặc hủy đơn hàng này trước đó, không thể tiếp nhận lại.', 422);

            // 2. Kiểm tra chuyến đi
            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Chuyến xe không tồn tại hoặc đã hết hạn.', 404);

            // Kiểm tra trạng thái tranh chấp (Double booking)
            $this->validate(
                $ride->status === RideStatus::PENDING && $ride->driver_id === null,
                'Đơn hàng đã được tài xế khác tiếp nhận trước đó.',
                422
            );

            // 3. Thực hiện chuyển đổi trạng thái (Giao dịch DB)
            $rideUpdated = $this->rideRepository->acceptByDriver($ride->id, $driverProfile->user_id);
            $this->validate($rideUpdated, 'Thao tác thất bại. Vui lòng thử lại.', 500);

            // Cập nhật trạng thái tài xế sang Bận (BUSY)
            $driverUpdated = $this->driverProfileRepository->updateStatus($driverProfile->id, DriverStatus::BUSY);
            $this->validate($driverUpdated, 'Lỗi hệ thống khi cập nhật trạng thái tài xế.', 500);

            // 4. Thông báo cho khách hàng qua Realtime
            event(new RideAccepted($ride->id, $driverProfile->id));

            $ride->refresh();
            return $ride->toArray();
        }, useTransaction: true);
    }

    /**
     * Tài xế từ chối chuyến đi được chỉ định (UC-33 Reject).
     */
    public function rejectOrder(RejectOrderDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $driverProfile = $this->driverProfileRepository->findByUserId($dto->userId);
            $this->validate($driverProfile !== null, 'Hồ sơ tài xế không tồn tại.', 404);

            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Chuyến xe không tồn tại.', 404);
            $this->validate($ride->status === RideStatus::PENDING, 'Đơn không còn ở trạng thái chờ.', 422);

            $this->rideRepository->rejectByDriver($ride->id, $driverProfile->user_id);

            event(new RideRejected($ride->id, $driverProfile->id));

            return $this->success([], 'Đã từ chối tiếp nhận đơn hàng.');
        }, useTransaction: true);
    }

    /**
     * Tài xế hủy chuyến xe sau khi đã tiếp nhận (UC-33 Cancel Order).
     *
     * Chú ý: Việc hủy chuyến sau khi nhận sẽ bị tính Penalty (Thời gian nghỉ - Cooldown).
     * Mức phạt tăng nặng nếu:
     * - Hủy khi đã di chuyển gần đến điểm đón (< 200m).
     * - Hủy nhiều lần trong ngày (> 3 lần).
     */
    public function cancelOrder(CancelOrderDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Xác thực tài xế và chuyến xe
            $driverProfile = $this->driverProfileRepository->findByUserId($dto->userId);
            $this->validate($driverProfile !== null, 'Hồ sơ tài xế không tồn tại.', 404);

            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Chuyến xe không tồn tại.', 404);

            if ($ride->status === RideStatus::CANCELLED) {
                $this->throw('Chuyến xe đã bị hủy từ trước.', 422);
            }

            $this->validate($ride->driver_id === $driverProfile->user_id, 'Bạn không có quyền thao tác trên đơn này.', 403);

            // Chỉ được hủy khi đang chờ đón khách, hoặc đã đón khách nhưng gặp sự cố
            $this->validate(
                in_array($ride->status, [RideStatus::ACCEPTED, RideStatus::IN_PROGRESS, RideStatus::PICKED_UP]),
                'Trạng thái hiện tại không thể thực hiện lệnh hủy.',
                422
            );

            // 2. Thực hiện hủy đơn và lưu lý do
            $this->rideRepository->cancelByDriver($ride->id, (string) $dto->reason->value);

            // Ghi nhận vào danh sách từ chối để không hiển thị lại cho tài xế này (Anti-re-acceptance)
            $this->rideRepository->rejectByDriver($ride->id, $driverProfile->user_id);

            // 3. Tính toán hình phạt (Penalty System)
            $penaltyMinutes = 0;

            // Kiểm tra xem có đang đứng gần điểm đón không
            if ($dto->currentLat !== null && $dto->currentLng !== null) {
                $distanceToPickup = $this->calculateDistance(
                    (float) $dto->currentLat,
                    (float) $dto->currentLng,
                    (float) $ride->pickup_lat,
                    (float) $ride->pickup_lng
                );
                if ($distanceToPickup <= 200) {
                    $penaltyMinutes = 30; // Hình phạt 30 phút do hủy sát giờ đón
                }
            }

            // Kiểm tra số lần hủy trong ngày
            $newCancelCount = $this->driverProfileRepository->incrementCancelCount($driverProfile->id);
            if ($newCancelCount >= 3) {
                $penaltyMinutes = max($penaltyMinutes, 60); // Hình phạt 60 phút do hủy quá nhiều
            }

            // Áp dụng trạng thái nghỉ (Cooldown) nếu có phạt
            if ($penaltyMinutes > 0) {
                $this->driverProfileRepository->setCooldown($driverProfile->id, $penaltyMinutes);
            } else {
                // Nếu không bị phạt, đưa tài xế trở lại trạng thái sẵn sàng (ACTIVE)
                $this->driverProfileRepository->updateStatus($driverProfile->id, DriverStatus::ACTIVE);
            }

            // 4. Thông báo cho các bên liên quan qua Realtime
            event(new RideCancelled($ride->id, $driverProfile->id, $dto->reason->getLabel()));

            return $this->success([], 'Hủy chuyến xe thành công.');
        }, useTransaction: true);
    }

    /**
     * Helper tính khoảng cách theo công thức Haversine (đơn vị: mét)
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
