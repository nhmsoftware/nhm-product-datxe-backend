<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Finance\DTO\ProcessRefundDTO;
use App\Modules\Finance\Http\Requests\ProcessRefundRequest;
use App\Modules\Finance\Interfaces\RefundServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class RefundController extends BaseController
{
    public function __construct(
        private readonly RefundServiceInterface $refundService,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/refunds',
        summary: 'Danh sách yêu cầu hoàn tiền (UC-109)',
        security: [['sanctum' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->refundService->list($request->all());
        return $this->sendSuccess($result->getData());
    }

    #[OA\Get(
        path: '/api/v1/admin/refunds/{id}',
        summary: 'Chi tiết yêu cầu hoàn tiền (UC-109)',
        security: [['sanctum' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->refundService->detail($id);
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData());
    }

    #[OA\Post(
        path: '/api/v1/admin/refunds/{id}/process',
        summary: 'Xử lý yêu cầu hoàn tiền (UC-109)',
        security: [['sanctum' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'APPROVED', description: 'APPROVED, REJECTED, COMPLETED'),
                    new OA\Property(property: 'amount', type: 'number', example: 50000, description: 'Số tiền hoàn trả (bắt buộc khi APPROVED)'),
                    new OA\Property(property: 'note', type: 'string', example: 'Đã liên hệ khách hàng và hoàn tiền qua ngân hàng.'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ'),
            new OA\Response(response: 404, description: 'Không tìm thấy yêu cầu'),
        ]
    )]
    public function process(ProcessRefundRequest $request, string $id): JsonResponse
    {
        $result = $this->refundService->process(ProcessRefundDTO::fromRequest($request, $id));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
