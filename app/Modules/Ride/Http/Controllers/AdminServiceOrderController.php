<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Ride\DTO\AdminCancelRideBookingDTO;
use App\Modules\Ride\DTO\AdminCreateDeliveryOrderDTO;
use App\Modules\Ride\DTO\AdminUpdateDeliveryOrderDTO;
use App\Modules\Ride\Http\Requests\AdminCreateDeliveryOrderRequest;
use App\Modules\Ride\Http\Requests\AdminUpdateDeliveryOrderRequest;
use App\Modules\Ride\Http\Resources\AdminServiceOrderResource;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Controller quản lý đơn dịch vụ (Giao hàng, Đồ ăn) dành cho Admin.
 * Tách biệt hoàn toàn với AdminScheduledRideController (chuyến xe hành khách).
 */
final class AdminServiceOrderController extends BaseController
{
    public function __construct(
        private readonly RideServiceInterface $rideService
    ) {}

    #[OA\Post(
        path: '/api/v1/admin/services',
        summary: 'UC-136: Tạo đơn giao hàng thủ công',
        security: [['sanctum' => []]],
        tags: ['Admin Services'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['sender_name', 'sender_phone', 'pickup_address', 'receiver_name', 'receiver_phone', 'destination_address', 'goods_type', 'total_price'],
                properties: [
                    new OA\Property(property: 'sender_name', type: 'string'),
                    new OA\Property(property: 'sender_phone', type: 'string'),
                    new OA\Property(property: 'pickup_address', type: 'string'),
                    new OA\Property(property: 'pickup_lat', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'pickup_lng', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'receiver_name', type: 'string'),
                    new OA\Property(property: 'receiver_phone', type: 'string'),
                    new OA\Property(property: 'destination_address', type: 'string'),
                    new OA\Property(property: 'destination_lat', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'destination_lng', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'goods_type', type: 'string'),
                    new OA\Property(property: 'goods_note', type: 'string', nullable: true),
                    new OA\Property(property: 'total_price', type: 'number', format: 'float'),
                    new OA\Property(property: 'distance_km', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'duration_minutes', type: 'integer', nullable: true),
                    new OA\Property(property: 'driver_id', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tạo đơn giao hàng thành công'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function store(AdminCreateDeliveryOrderRequest $request): JsonResponse
    {
        $result = $this->rideService->createAdminDeliveryOrder(AdminCreateDeliveryOrderDTO::fromRequest($request));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
    }

    /**
     * Danh sách đơn dịch vụ cho Admin quản lý.
     */
    #[OA\Get(
        path: '/api/v1/admin/services',
        summary: 'Danh sách đơn dịch vụ (Admin)',
        description: 'Lấy danh sách tất cả đơn giao hàng và đặt đồ ăn. Hỗ trợ lọc theo status, ride_type, keyword và phân trang.',
        security: [['sanctum' => []]],
        tags: ['Admin Services'],
        parameters: [
            new OA\Parameter(name: 'keyword',  in: 'query', description: 'Tìm theo ID, địa chỉ hoặc SĐT khách', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status',   in: 'query', description: 'Trạng thái (waiting, assigned, completed, canceled)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ride_type',in: 'query', description: 'Loại dịch vụ: 4=Giao hàng, 6=Đồ ăn', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page',     in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'no_pagination', in: 'query', description: 'Lấy toàn bộ không phân trang (truyền 1)', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền Admin'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->rideService->listServiceOrdersForAdmin($request->all());

        $data = $result->getData();

        if ($data === null) {
            return $this->sendSuccess([], 'Không có dữ liệu.');
        }

        $resource = AdminServiceOrderResource::collection($data);

        return response()->json([
            'success' => true,
            'data'    => $resource->response()->getData(true),
            'message' => 'Lấy danh sách đơn dịch vụ thành công.',
        ]);
    }

    /**
     * Chi tiết một đơn dịch vụ.
     */
    #[OA\Get(
        path: '/api/v1/admin/services/{id}',
        summary: 'Chi tiết đơn dịch vụ (Admin)',
        security: [['sanctum' => []]],
        tags: ['Admin Services'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy đơn'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->rideService->listServiceOrdersForAdmin([
            'keyword' => $id,
            'no_pagination' => 1,
        ]);

        $data = $result->getData();

        if ($data === null || (is_object($data) && method_exists($data, 'isEmpty') && $data->isEmpty())) {
            return $this->sendError('Không tìm thấy đơn dịch vụ.', 404);
        }

        $order = is_iterable($data)
            ? collect($data)->firstWhere('id', $id)
            : null;

        if ($order === null) {
            return $this->sendError('Không tìm thấy đơn dịch vụ.', 404);
        }

        return $this->sendSuccess(
            new AdminServiceOrderResource($order),
            'Lấy chi tiết đơn dịch vụ thành công.'
        );
    }

    #[OA\Put(
        path: '/api/v1/admin/services/{id}',
        summary: 'UC-137: Cập nhật đơn giao hàng',
        security: [['sanctum' => []]],
        tags: ['Admin Services'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['sender_name', 'sender_phone', 'pickup_address', 'receiver_name', 'receiver_phone', 'destination_address', 'goods_type', 'total_price'],
                properties: [
                    new OA\Property(property: 'sender_name', type: 'string'),
                    new OA\Property(property: 'sender_phone', type: 'string'),
                    new OA\Property(property: 'pickup_address', type: 'string'),
                    new OA\Property(property: 'pickup_lat', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'pickup_lng', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'receiver_name', type: 'string'),
                    new OA\Property(property: 'receiver_phone', type: 'string'),
                    new OA\Property(property: 'destination_address', type: 'string'),
                    new OA\Property(property: 'destination_lat', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'destination_lng', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'goods_type', type: 'string'),
                    new OA\Property(property: 'goods_note', type: 'string', nullable: true),
                    new OA\Property(property: 'total_price', type: 'number', format: 'float'),
                    new OA\Property(property: 'distance_km', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'duration_minutes', type: 'integer', nullable: true),
                    new OA\Property(property: 'driver_id', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật đơn giao hàng thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy đơn giao hàng'),
        ]
    )]
    public function update(AdminUpdateDeliveryOrderRequest $request, string $id): JsonResponse
    {
        $result = $this->rideService->updateAdminDeliveryOrder(AdminUpdateDeliveryOrderDTO::fromRequest($request, $id));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/admin/services/{id}',
        summary: 'UC-137: Cancel Delivery Order (soft cancel)',
        security: [['sanctum' => []]],
        tags: ['Admin Services'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'reason', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hủy đơn giao hàng thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy đơn giao hàng'),
        ]
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        $result = $this->rideService->cancelAdminDeliveryOrder(AdminCancelRideBookingDTO::fromRequest($request, $id));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
