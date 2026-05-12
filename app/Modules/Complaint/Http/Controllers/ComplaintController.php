<?php

declare(strict_types=1);

namespace App\Modules\Complaint\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Complaint\DTO\HandleComplaintDTO;
use App\Modules\Complaint\Http\Requests\HandleComplaintRequest;
use App\Modules\Complaint\Interfaces\ComplaintServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class ComplaintController extends BaseController
{
    public function __construct(
        private readonly ComplaintServiceInterface $complaintService,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/complaints',
        summary: 'Danh sách khiếu nại (UC-108)',
        security: [['sanctum' => []]],
        tags: ['Complaint'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->complaintService->list($request->all());
        return $this->sendSuccess($result->getData());
    }

    #[OA\Get(
        path: '/api/v1/admin/complaints/{id}',
        summary: 'Chi tiết khiếu nại (UC-108)',
        security: [['sanctum' => []]],
        tags: ['Complaint'],
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
        $result = $this->complaintService->detail($id);
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData());
    }

    #[OA\Post(
        path: '/api/v1/admin/complaints/{id}/handle',
        summary: 'Xử lý khiếu nại (UC-108)',
        security: [['sanctum' => []]],
        tags: ['Complaint'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['action'],
                properties: [
                    new OA\Property(property: 'action', type: 'string', example: 'WARN_DRIVER', description: 'REFUND, WARN_DRIVER, WARN_CUSTOMER, REJECT, REQUEST_INFO'),
                    new OA\Property(property: 'note', type: 'string', example: 'Cảnh báo tài xế về thái độ phục vụ.'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ'),
            new OA\Response(response: 404, description: 'Không tìm thấy khiếu nại'),
        ]
    )]
    public function handle(HandleComplaintRequest $request, string $id): JsonResponse
    {
        $result = $this->complaintService->handle(HandleComplaintDTO::fromRequest($request, $id));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
