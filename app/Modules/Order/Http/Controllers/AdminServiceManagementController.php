<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Order\DTO\AdminCreateFoodOrderDTO;
use App\Modules\Order\DTO\AdminUpdateFoodOrderDTO;
use App\Modules\Order\Http\Requests\AdminCreateFoodOrderRequest;
use App\Modules\Order\Http\Requests\AdminUpdateFoodOrderRequest;
use App\Modules\Order\Interfaces\AdminOrderServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class AdminServiceManagementController extends BaseController
{
    public function __construct(
        private readonly AdminOrderServiceInterface $adminOrderService
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/services/orders',
        summary: 'Lấy danh sách đơn hàng dịch vụ (Admin)',
        description: 'Lấy danh sách các đơn hàng đồ ăn và giao hàng cho quản trị viên.',
        security: [['sanctum' => []]],
        tags: ['Admin Service Management'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền Admin')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->adminOrderService->getServiceOrders();
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        return $this->sendSuccess($result->getData(), 'Lấy danh sách đơn hàng dịch vụ thành công.');
    }

    #[OA\Get(
        path: '/api/v1/admin/services/orders/{id}',
        summary: 'Lấy chi tiết đơn hàng dịch vụ (Admin)',
        security: [['sanctum' => []]],
        tags: ['Admin Service Management'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy đơn hàng')
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->adminOrderService->getServiceOrderDetail($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy chi tiết đơn hàng dịch vụ thành công.');
    }

    #[OA\Post(
        path: '/api/v1/admin/services/orders',
        summary: 'UC-138: Tạo đơn đồ ăn thủ công',
        security: [['sanctum' => []]],
        tags: ['Admin Service Management'],
        responses: [
            new OA\Response(response: 201, description: 'Tạo đơn đồ ăn thành công'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ')
        ]
    )]
    public function store(AdminCreateFoodOrderRequest $request): JsonResponse
    {
        $result = $this->adminOrderService->createFoodOrder(AdminCreateFoodOrderDTO::fromRequest($request));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
    }

    #[OA\Put(
        path: '/api/v1/admin/services/orders/{id}',
        summary: 'UC-139: Cập nhật đơn đồ ăn',
        security: [['sanctum' => []]],
        tags: ['Admin Service Management'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật đơn đồ ăn thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy đơn đồ ăn')
        ]
    )]
    public function update(AdminUpdateFoodOrderRequest $request, string $id): JsonResponse
    {
        $result = $this->adminOrderService->updateFoodOrder(AdminUpdateFoodOrderDTO::fromRequest($request, $id));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/admin/services/orders/{id}',
        summary: 'UC-139: Hủy đơn đồ ăn',
        security: [['sanctum' => []]],
        tags: ['Admin Service Management'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'reason', in: 'query', required: false, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hủy đơn đồ ăn thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy đơn đồ ăn')
        ]
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        $result = $this->adminOrderService->cancelFoodOrder($id, $request->input('reason'));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/admin/services/orders/assign',
        summary: 'Chỉ định tài xế cho đơn hàng dịch vụ (Admin)',
        description: 'Gán trực tiếp một tài xế cho đơn hàng đồ ăn hoặc giao hàng.',
        security: [['sanctum' => []]],
        tags: ['Admin Service Management'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id', 'driver_id'],
                properties: [
                    new OA\Property(property: 'order_id', type: 'string', example: '123'),
                    new OA\Property(property: 'driver_id', type: 'string', example: '456'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ')
        ]
    )]
    public function assign(Request $request): JsonResponse
    {
        $orderId = $request->input('order_id');
        $driverId = $request->input('driver_id');

        if (empty($orderId) || empty($driverId)) {
            return $this->sendError('Thiếu thông tin đơn hàng hoặc tài xế.', 400);
        }

        $result = $this->adminOrderService->assignDriver((string) $orderId, (string) $driverId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Phân phối chuyến xe thành công.');
    }

    #[OA\Post(
        path: '/api/v1/admin/services/orders/push-to-pool',
        summary: 'Đẩy đơn hàng dịch vụ ra pool (Admin)',
        description: 'Đẩy một hoặc nhiều đơn hàng dịch vụ vào pool tìm tài xế.',
        security: [['sanctum' => []]],
        tags: ['Admin Service Management'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_ids'],
                properties: [
                    new OA\Property(property: 'order_ids', type: 'array', items: new OA\Items(type: 'string'), example: ['123', '456']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công')
        ]
    )]
    public function pushToPool(Request $request): JsonResponse
    {
        $orderIds = $request->input('order_ids');

        if (empty($orderIds) || !is_array($orderIds)) {
            return $this->sendError('Danh sách đơn hàng không hợp lệ.', 400);
        }

        $result = $this->adminOrderService->pushToPool($orderIds);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Phân phối chuyến xe ra pool thành công.');
    }
}
