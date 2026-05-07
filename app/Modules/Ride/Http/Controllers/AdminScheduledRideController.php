<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Ride\DTO\AssignInternalDriverDTO;
use App\Modules\Ride\DTO\BulkPushToPoolDTO;
use App\Modules\Ride\Http\Requests\AdminAssignDriverRequest;
use App\Modules\Ride\Http\Requests\AdminBulkPushRequest;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminScheduledRideController extends BaseController
{
    public function __construct(
        private readonly RideServiceInterface $rideService
    ) {}

    /**
     * UC-122: Danh sách các chuyến xe đặt trước.
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->rideService->listScheduledRidesForAdmin($request->all());
        return $this->sendSuccess($result->getData(), 'Lấy danh sách chuyến đặt trước thành công.');
    }

    /**
     * UC-122: Chi tiết chuyến xe đặt trước.
     */
    public function show(string $id): JsonResponse
    {
        // Sử dụng getRideDetail nhưng có thể cần Admin detail đặc thù nếu muốn.
        // Tạm thời dùng getRideDetail (reused UC-29 logic)
        $result = $this->rideService->getScheduledRideDetail($id, ''); // DriverId rỗng để lấy detail chung
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getStatusCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy thông tin chi tiết thành công.');
    }

    /**
     * UC-122: Phân phối cho đội xe nhà (Force Assign).
     */
    public function assign(AdminAssignDriverRequest $request): JsonResponse
    {
        $dto = AssignInternalDriverDTO::fromRequest($request);
        $result = $this->rideService->assignInternalDriver($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getStatusCode());
        }

        return $this->sendSuccess($result->getData(), 'Phân phối chuyến xe cho đội xe nhà thành công.');
    }

    /**
     * UC-122: Đẩy chuyến ra danh sách chung cho tài xế ngoài.
     */
    public function pushToPool(AdminBulkPushRequest $request): JsonResponse
    {
        $dto = BulkPushToPoolDTO::fromRequest($request);
        $result = $this->rideService->pushScheduledRidesToPool($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getStatusCode());
        }

        return $this->sendSuccess($result->getData(), 'Phân phối chuyến xe ra pool thành công.');
    }
}
