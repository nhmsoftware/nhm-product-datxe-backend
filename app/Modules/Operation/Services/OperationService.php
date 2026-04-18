<?php

declare(strict_types=1);

namespace App\Modules\Operation\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Operation\DTO\UpdateLocationDTO;
use App\Modules\Operation\DTO\GetNavigationDTO;
use App\Modules\Operation\Events\UserLocationUpdated;
use App\Modules\Operation\Interfaces\OperationServiceInterface;
use App\Modules\Operation\Interfaces\LocationRepositoryInterface;
use App\Modules\Ride\Interfaces\MapServiceInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Ride;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\User;

/**
 * Service xử lý các nghiệp vụ vận hành: Theo dõi vị trí và Dẫn đường.
 */
final class OperationService extends BaseService implements OperationServiceInterface
{
    public function __construct(
        private readonly LocationRepositoryInterface      $locationRepository,
        private readonly MapServiceInterface               $mapService,
        private readonly RideRepositoryInterface          $rideRepository,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function updateLocation(UpdateLocationDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            /** @var User $user */
            $user = auth()->user(); // Lấy user từ context (mặc dù DTO có ID nhưng ta dùng auth cho an toàn role)

            $updated = false;

            // 1. Cập nhật DB dựa trên role
            if ($dto->userId && $user && $user->role === UserRole::Driver) {
                $updated = $this->locationRepository->updateDriverLocation($dto->userId, $dto->lat, $dto->lng);
            } elseif ($dto->userId && $user && $user->role === UserRole::Customer) {
                $updated = $this->locationRepository->updateCustomerLocation($dto->userId, $dto->lat, $dto->lng);
            }

            // 2. Phát sự kiện realtime (Redis/Socket.io)
            if ($user) {
                event(new UserLocationUpdated(
                    userId: $dto->userId,
                    role:   $user->role->value,
                    lat:    $dto->lat,
                    lng:    $dto->lng
                ));
            }

            return [
                'updated' => $updated,
            ];
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function getNavigation(GetNavigationDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // 1. Lấy thông tin chuyến xe
            /** @var Ride|null $ride */
            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Chuyến xe không tồn tại.', 404);

            // 2. Xác định điểm đi và điểm đến tùy theo Actor và Trạng thái chuyến xe
            $originLat = 0.0;
            $originLng = 0.0;
            $destLat   = 0.0;
            $destLng   = 0.0;

            if ($dto->role === UserRole::Driver->value) {
                // TÀI XẾ XEM CHỈ ĐƯỜNG
                $this->validate($ride->driver_id === $dto->userId, 'Bạn không phải tài xế của chuyến này.', 403);

                // Lấy vị trí hiện tại của tài xế từ Repository (Ưu tiên Redis)
                $driverLocation = $this->locationRepository->getDriverLocation($dto->userId);
                $this->validate($driverLocation !== null, 'Không tìm thấy vị trí hiện tại của bạn. Vui lòng bật GPS.', 400);

                $originLat = (float) $driverLocation['lat'];
                $originLng = (float) $driverLocation['lng'];

                if ($ride->status === RideStatus::ACCEPTED) {
                    // Đang đến điểm đón
                    $destLat = (float) $ride->pickup_lat;
                    $destLng = (float) $ride->pickup_lng;
                } elseif ($ride->status === RideStatus::IN_PROGRESS) {
                    // Đang đến điểm trả
                    $destLat = (float) $ride->destination_lat;
                    $destLng = (float) $ride->destination_lng;
                } else {
                    $this->throw('Trạng thái chuyến xe không hỗ trợ chỉ đường.', 400);
                }

            } elseif ($dto->role === UserRole::Customer->value) {
                // KHÁCH HÀNG THEO DÕI TÀI XẾ
                $this->validate($ride->customer_id === $dto->userId, 'Bạn không phải khách hàng của chuyến này.', 403);
                $this->validate($ride->driver_id !== null, 'Tài xế chưa nhận đơn.', 400);

                // Lấy vị trí tài xế từ Repository (Ưu tiên Redis)
                $driverLocation = $this->locationRepository->getDriverLocation($ride->driver_id);
                $this->validate($driverLocation !== null, 'Không thể lấy vị trí tài xế.', 400);

                $originLat = (float) $driverLocation['lat'];
                $originLng = (float) $driverLocation['lng'];

                // Đích đến là vị trí khách hàng (Pickup) hoặc điểm đến (Destination) tùy trạng thái
                if ($ride->status === RideStatus::ACCEPTED) {
                    $destLat = (float) $ride->pickup_lat;
                    $destLng = (float) $ride->pickup_lng;
                } else {
                    $destLat = (float) $ride->destination_lat;
                    $destLng = (float) $ride->destination_lng;
                }
            } else {
                $this->throw('Bạn không có quyền xem chỉ đường cho chuyến này.', 403);
            }

            // 3. Gọi Map Service lấy Route
            $direction = $this->mapService->getDirection($originLat, $originLng, $destLat, $destLng);

            return $direction->toArray();
        });
    }
}
