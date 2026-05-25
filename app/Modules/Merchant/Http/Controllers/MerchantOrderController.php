<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Order\DTO\GetMerchantOrdersFilterDTO;
use App\Modules\Order\Interfaces\OrderServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class MerchantOrderController extends BaseController
{
    public function __construct(
        private readonly OrderServiceInterface $orderService
    ) {}

    #[OA\Get(
        path: '/api/v1/merchant/orders',
        summary: 'Xem danh sách đơn hàng của cửa hàng (UC-69.1)',
        security: [['sanctum' => []]],
        tags: ['Merchant Order'],
        parameters: [
            new OA\Parameter(
                name: 'status_group',
                in: 'query',
                description: 'Nhóm trạng thái đơn hàng muốn xem (new: Đơn mới, preparing: Đang chuẩn bị, processed: Đã xử lý)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['new', 'preparing', 'processed'])
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Số lượng đơn hàng trên mỗi trang (mặc định 20)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Số trang cần lấy (mặc định 1)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'overview',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_orders_today', type: 'integer', example: 10),
                                new OA\Property(property: 'revenue_today', type: 'number', format: 'float', example: 500000.0),
                                new OA\Property(property: 'performance_today', type: 'number', format: 'float', example: 90.0),
                            ]
                        ),
                        new OA\Property(
                            property: 'orders',
                            type: 'object',
                            description: 'Thông tin phân trang và danh sách đơn hàng',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'total', type: 'integer', example: 15),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Lỗi tham số đầu vào'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền truy cập'),
            new OA\Response(response: 404, description: 'Không tìm thấy cửa hàng')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $merchantProfile = $request->user()->merchantProfile;
        if (!$merchantProfile) {
            return $this->sendError('Tài khoản của bạn chưa cấu hình hồ sơ Merchant.', 404);
        }

        $merchantId = (string) $merchantProfile->id;
        $dto = GetMerchantOrdersFilterDTO::fromRequest($request, $merchantId);

        $result = $this->orderService->getMerchantOrders($dto);
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Tải danh sách đơn hàng thành công.');
    }


    #[OA\Post(path: '/api/v1/merchant/orders/{id}/accept', summary: 'Nhận đơn hàng (UC-71)', security: [['sanctum' => []]], tags: ['Merchant Order'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Nhận đơn thành công')]
    public function accept(Request $request, string $id): JsonResponse
    {
        $merchantId = (string) $request->user()->merchantProfile->id;
        $result = $this->orderService->acceptFoodOrder($id, $merchantId);

        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Nhận đơn hàng thành công.');
    }

    #[OA\Post(path: '/api/v1/merchant/orders/{id}/reject', summary: 'Từ chối đơn hàng (UC-72)', security: [['sanctum' => []]], tags: ['Merchant Order'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'reason', type: 'string', example: 'Hết món')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Từ chối đơn thành công')]
    public function reject(Request $request, string $id): JsonResponse
    {
        $merchantId = (string) $request->user()->merchantProfile->id;
        $reason = $request->input('reason');
        $result = $this->orderService->rejectFoodOrder($id, $merchantId, $reason);

        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Từ chối đơn hàng thành công.');
    }

    #[OA\Post(path: '/api/v1/merchant/orders/{id}/preparing', summary: 'Đánh dấu đang chuẩn bị (UC-64)', security: [['sanctum' => []]], tags: ['Merchant Order'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Cập nhật thành công')]
    public function preparing(Request $request, string $id): JsonResponse
    {
        $merchantId = (string) $request->user()->merchantProfile->id;
        $result = $this->orderService->markPreparing($id, $merchantId);

        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Đơn hàng đang được chuẩn bị.');
    }

    #[OA\Post(path: '/api/v1/merchant/orders/{id}/ready', summary: 'Đánh dấu sẵn sàng giao (UC-73)', security: [['sanctum' => []]], tags: ['Merchant Order'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Cập nhật thành công')]
    public function ready(Request $request, string $id): JsonResponse
    {
        $merchantId = (string) $request->user()->merchantProfile->id;
        $result = $this->orderService->markReady($id, $merchantId);

        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Đơn hàng đã sẵn sàng để giao.');
    }

    #[OA\Post(path: '/api/v1/merchant/orders/{id}/cancel', summary: 'Hủy đơn hàng (UC-75)', security: [['sanctum' => []]], tags: ['Merchant Order'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'reason', type: 'string', example: 'Khách hàng yêu cầu hủy')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Hủy đơn thành công')]
    public function cancel(Request $request, string $id): JsonResponse
    {
        $merchantId = (string) $request->user()->merchantProfile->id;
        $reason = $request->input('reason');
        $result = $this->orderService->cancelFoodOrder($id, $merchantId, $reason);

        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Đơn hàng đã được hủy.');
    }

    #[OA\Post(path: '/api/v1/merchant/orders/{id}/cancellation/handle', summary: 'Xử lý yêu cầu hủy (UC-74)', security: [['sanctum' => []]], tags: ['Merchant Order'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            required: ['action'],
            properties: [
                new OA\Property(property: 'action', type: 'string', enum: ['accept', 'reject'], example: 'accept')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Xử lý thành công')]
    public function handleCancellation(Request $request, string $id): JsonResponse
    {
        $merchantId = (string) $request->user()->merchantProfile->id;
        $action = $request->input('action');

        $result = $this->orderService->handleCancellation($id, $merchantId, $action);

        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());

        $message = $action === 'accept' ? 'Đã chấp nhận hủy đơn hàng.' : 'Đã từ chối hủy đơn hàng.';
        return $this->sendSuccess($result->getData(), $message);
    }

    #[OA\Get(path: '/api/v1/merchant/orders/{id}', summary: 'Xem chi tiết đơn hàng (UC-70)', security: [['sanctum' => []]], tags: ['Merchant Order'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Thành công')]
    #[OA\Response(response: 404, description: 'Đơn hàng không tồn tại')]
    public function show(Request $request, string $id): JsonResponse
    {
        $merchantId = (string) $request->user()->merchantProfile->id;
        $result = $this->orderService->getOrderDetail($id, 'food', $merchantId);

        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Tải chi tiết đơn hàng thành công.');
    }
}
