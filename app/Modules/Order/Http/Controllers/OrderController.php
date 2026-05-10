<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Order\DTO\GetOrderHistoryFilterDTO;
use App\Modules\Order\Http\Requests\GetOrderHistoryRequest;
use App\Modules\Order\Interfaces\OrderServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class OrderController extends BaseController
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
    ) {}

    #[OA\Get(
        path: '/api/v1/customer/orders',
        summary: 'Xem lịch sử đơn hàng (UC-19)',
        description: 'Lấy danh sách các chuyến xe và đơn hàng đồ ăn của khách hàng, có hỗ trợ lọc và phân trang.',
        security: [['sanctum' => []]],
        tags: ['Order History'],
        parameters: [
            new OA\Parameter(name: 'service_type', in: 'query', description: 'Loại dịch vụ (ride/food)', schema: new OA\Schema(type: 'string', enum: ['ride', 'food'])),
            new OA\Parameter(name: 'status', in: 'query', description: 'Trạng thái đơn', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'start_date', in: 'query', description: 'Từ ngày (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', description: 'Đến ngày (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Số bản ghi mỗi trang', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Danh sách đơn hàng'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
        ]
    )]
    public function index(GetOrderHistoryRequest $request): JsonResponse
    {
        $result = $this->orderService->getHistory(
            GetOrderHistoryFilterDTO::fromRequest($request)
        );

        return $this->sendSuccess($result->getData(), 'Lấy lịch sử đơn hàng thành công.');
    }

    #[OA\Get(
        path: '/api/v1/customer/orders/{orderId}',
        summary: 'Xem chi tiết đơn hàng (UC-19)',
        description: 'Lấy thông tin chi tiết của một đơn hàng hoặc chuyến xe cụ thể.',
        security: [['sanctum' => []]],
        tags: ['Order History'],
        parameters: [
            new OA\Parameter(name: 'orderId', in: 'path', description: 'ID đơn hàng', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'service_type', in: 'query', description: 'Loại dịch vụ (ride/food)', required: true, schema: new OA\Schema(type: 'string', enum: ['ride', 'food'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Chi tiết đơn hàng'),
            new OA\Response(response: 404, description: 'Không tìm thấy đơn hàng'),
        ]
    )]
    public function show(string $orderId, Request $request): JsonResponse
    {
        $serviceType = $request->query('service_type');
        if (!$serviceType) {
            return $this->sendError('Vui lòng cung cấp service_type.', 400);
        }

        $result = $this->orderService->getOrderDetail($orderId, $serviceType);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy chi tiết đơn hàng thành công.');
    }
}
