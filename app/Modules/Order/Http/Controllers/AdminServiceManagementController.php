<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers;

use App\Core\Controller\BaseController;
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
