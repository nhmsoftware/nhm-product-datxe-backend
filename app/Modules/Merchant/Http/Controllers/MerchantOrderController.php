<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Order\Interfaces\OrderServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class MerchantOrderController extends BaseController
{
    public function __construct(
        private readonly OrderServiceInterface $orderService
    ) {}

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
