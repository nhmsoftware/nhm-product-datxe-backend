<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Ride\DTO\AdminCancelRideBookingDTO;
use App\Modules\Ride\DTO\AdminCreateRideBookingDTO;
use App\Modules\Ride\DTO\AdminUpdateRideBookingDTO;
use App\Modules\Ride\DTO\AssignInternalDriverDTO;
use App\Modules\Ride\DTO\BulkPushToPoolDTO;
use App\Modules\Ride\Http\Requests\AdminCreateRideBookingRequest;
use App\Modules\Ride\Http\Requests\AdminUpdateRideBookingRequest;
use App\Modules\Ride\Http\Requests\AdminAssignDriverRequest;
use App\Modules\Ride\Http\Requests\AdminBulkPushRequest;
use App\Modules\Ride\Http\Resources\AdminScheduledRideResource;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class AdminScheduledRideController extends BaseController
{
    public function __construct(
        private readonly RideServiceInterface $rideService
    ) {}

    /**
     * UC-122: Danh sách các chuyến xe đặt trước.
     */
    #[OA\Get(
        path: '/api/v1/admin/rides/scheduled',
        summary: 'UC-122: Lấy danh sách chuyến xe đặt trước (Admin)',
        description: 'Lấy toàn bộ danh sách các chuyến đi tỉnh và sân bay đang chờ xử lý.',
        security: [['sanctum' => []]],
        tags: ['Admin Scheduled Rides'],
        parameters: [
            new OA\Parameter(name: 'keyword', in: 'query', description: 'Tìm theo ID, địa chỉ hoặc SĐT khách', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Trạng thái (waiting, assigned, completed, canceled)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'no_pagination', in: 'query', description: 'Lấy toàn bộ không phân trang', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền Admin')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->rideService->listScheduledRidesForAdmin($request->all());
        
        $data = $result->getData();

        if ($data === null) {
            return $this->sendSuccess([], 'Không có dữ liệu.');
        }
        
        // Laravel Resource Collection tự động xử lý meta/links nếu là Paginator
        $resource = AdminScheduledRideResource::collection($data);

        // Trả về thẳng resource để Laravel tự xử lý cấu trúc phân trang chuẩn
        return response()->json([
            'success' => true,
            'data'    => $resource->response()->getData(true),
            'message' => 'Lấy danh sách chuyến đặt trước thành công.'
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/rides/scheduled',
        summary: 'UC-134: Tạo booking chuyến xe thủ công',
        security: [['sanctum' => []]],
        tags: ['Admin Scheduled Rides'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ride_type', 'customer_mode', 'pickup_address', 'destination_address', 'vehicle_type', 'total_price'],
                properties: [
                    new OA\Property(property: 'ride_type', type: 'integer', example: 1, description: '1: Chuyến xe thường, 2: Đi tỉnh, 3: Sân bay'),
                    new OA\Property(property: 'customer_mode', type: 'string', enum: ['existing', 'new'], example: 'existing'),
                    new OA\Property(property: 'customer_id', type: 'string', nullable: true, description: 'Bắt buộc khi chọn khách hàng hiện có'),
                    new OA\Property(property: 'customer_name', type: 'string', nullable: true, description: 'Bắt buộc khi nhập khách hàng mới'),
                    new OA\Property(property: 'customer_phone', type: 'string', nullable: true, description: 'Bắt buộc khi nhập khách hàng mới'),
                    new OA\Property(property: 'customer_email', type: 'string', format: 'email', nullable: true),
                    new OA\Property(property: 'pickup_address', type: 'string'),
                    new OA\Property(property: 'pickup_lat', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'pickup_lng', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'destination_address', type: 'string'),
                    new OA\Property(property: 'destination_lat', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'destination_lng', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'vehicle_type', type: 'integer'),
                    new OA\Property(property: 'total_price', type: 'number', format: 'float'),
                    new OA\Property(property: 'distance_km', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'duration_minutes', type: 'integer', nullable: true),
                    new OA\Property(property: 'driver_id', type: 'string', nullable: true),
                    new OA\Property(property: 'travel_date', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'travel_time', type: 'string', example: '08:30', nullable: true),
                    new OA\Property(property: 'airport_id', type: 'string', nullable: true),
                    new OA\Property(property: 'airport_direction', type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tạo chuyến xe thành công'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function store(AdminCreateRideBookingRequest $request): JsonResponse
    {
        $result = $this->rideService->createAdminRideBooking(AdminCreateRideBookingDTO::fromRequest($request));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
    }

    /**
     * UC-122: Chi tiết chuyến xe đặt trước.
     */
    #[OA\Get(
        path: '/api/v1/admin/rides/scheduled/{id}',
        summary: 'UC-122: Chi tiết chuyến xe đặt trước',
        security: [['sanctum' => []]],
        tags: ['Admin Scheduled Rides'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy chuyến xe')
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->rideService->getAdminRideDetail($id);
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess(
            new AdminScheduledRideResource($result->getData()),
            'Lấy thông tin chi tiết thành công.'
        );
    }

    #[OA\Put(
        path: '/api/v1/admin/rides/scheduled/{id}',
        summary: 'UC-135: Cập nhật booking chuyến xe',
        security: [['sanctum' => []]],
        tags: ['Admin Scheduled Rides'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ride_type', 'pickup_address', 'destination_address', 'vehicle_type', 'total_price'],
                properties: [
                    new OA\Property(property: 'ride_type', type: 'integer'),
                    new OA\Property(property: 'pickup_address', type: 'string'),
                    new OA\Property(property: 'pickup_lat', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'pickup_lng', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'destination_address', type: 'string'),
                    new OA\Property(property: 'destination_lat', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'destination_lng', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'vehicle_type', type: 'integer'),
                    new OA\Property(property: 'total_price', type: 'number', format: 'float'),
                    new OA\Property(property: 'distance_km', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'duration_minutes', type: 'integer', nullable: true),
                    new OA\Property(property: 'driver_id', type: 'string', nullable: true),
                    new OA\Property(property: 'travel_date', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'travel_time', type: 'string', nullable: true),
                    new OA\Property(property: 'airport_id', type: 'string', nullable: true),
                    new OA\Property(property: 'airport_direction', type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy chuyến xe'),
        ]
    )]
    public function update(AdminUpdateRideBookingRequest $request, string $id): JsonResponse
    {
        $result = $this->rideService->updateAdminRideBooking(AdminUpdateRideBookingDTO::fromRequest($request, $id));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/admin/rides/scheduled/{id}',
        summary: 'UC-135: Cancel Ride Booking (soft cancel)',
        security: [['sanctum' => []]],
        tags: ['Admin Scheduled Rides'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'reason', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hủy thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy chuyến xe'),
        ]
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        $result = $this->rideService->cancelAdminRideBooking(AdminCancelRideBookingDTO::fromRequest($request, $id));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    /**
     * UC-122: Phân phối cho đội xe nhà (Force Assign).
     */
    #[OA\Post(
        path: '/api/v1/admin/rides/scheduled/assign',
        summary: 'UC-122: Chỉ định tài xế cho chuyến xe (Admin)',
        description: 'Gán trực tiếp một tài xế cho chuyến xe đang chờ.',
        security: [['sanctum' => []]],
        tags: ['Admin Scheduled Rides'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ride_id', 'driver_id'],
                properties: [
                    new OA\Property(property: 'ride_id', type: 'string', example: '12345'),
                    new OA\Property(property: 'driver_id', type: 'string', example: '67890'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ')
        ]
    )]
    public function assign(AdminAssignDriverRequest $request): JsonResponse
    {
        $dto = AssignInternalDriverDTO::fromRequest($request);
        $result = $this->rideService->assignInternalDriver($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Phân phối chuyến xe cho đội xe nhà thành công.');
    }

    /**
     * UC-122: Đẩy chuyến ra danh sách chung cho tài xế ngoài.
     */
    #[OA\Post(
        path: '/api/v1/admin/rides/scheduled/push-to-pool',
        summary: 'UC-122: Đẩy chuyến xe ra danh sách chung (Pool)',
        description: 'Làm cho chuyến xe hiển thị đối với tất cả tài xế phù hợp.',
        security: [['sanctum' => []]],
        tags: ['Admin Scheduled Rides'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ride_ids'],
                properties: [
                    new OA\Property(property: 'ride_ids', type: 'array', items: new OA\Items(type: 'string'), example: ['123', '456']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công')
        ]
    )]
    public function pushToPool(AdminBulkPushRequest $request): JsonResponse
    {
        $dto = BulkPushToPoolDTO::fromRequest($request);
        $result = $this->rideService->pushScheduledRidesToPool($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Phân phối chuyến xe ra pool thành công.');
    }
}
