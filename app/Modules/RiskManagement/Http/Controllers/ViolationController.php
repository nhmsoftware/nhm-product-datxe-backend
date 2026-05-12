<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\RiskManagement\DTO\WarnUserDTO;
use App\Modules\RiskManagement\Http\Requests\WarnDriverRequest;
use App\Modules\RiskManagement\Interfaces\ViolationServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class ViolationController extends BaseController
{
    public function __construct(
        private readonly ViolationServiceInterface $violationService,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/risk/violations/{userId}',
        summary: 'Lịch sử vi phạm của người dùng (UC-110)',
        security: [['sanctum' => []]],
        tags: ['RiskManagement'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy người dùng'),
        ]
    )]
    public function history(string $userId): JsonResponse
    {
        $result = $this->violationService->getHistory($userId);
        return $this->sendSuccess($result->getData());
    }

    #[OA\Post(
        path: '/api/v1/admin/risk/violations/{userId}/warn',
        summary: 'Gửi cảnh báo cho Driver (UC-110)',
        security: [['sanctum' => []]],
        tags: ['RiskManagement'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'reason'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', example: 'ATTITUDE', description: 'ATTITUDE, CANCELLATION, INCOMPLETE_TRIP, LATE_DELIVERY, FRAUD, OTHER'),
                    new OA\Property(property: 'reason', type: 'string', example: 'Tài xế có thái độ khiếm nhã với khách hàng.'),
                    new OA\Property(property: 'complaint_id', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Gửi cảnh báo thành công'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ hoặc thiếu thông tin'),
            new OA\Response(response: 404, description: 'Không tìm thấy Driver'),
        ]
    )]
    public function warn(WarnDriverRequest $request, string $userId): JsonResponse
    {
        $result = $this->violationService->warnUser(WarnUserDTO::fromRequest($request, $userId));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/admin/risk/violations/customer/{userId}/warn',
        summary: 'Gửi cảnh báo cho Customer (UC-111)',
        security: [['sanctum' => []]],
        tags: ['RiskManagement'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'reason'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', example: 'SPAM_BOOKING', description: 'ATTITUDE, CANCELLATION, FRAUD, SPAM_BOOKING, VOUCHER_ABUSE, HARASSMENT, OTHER'),
                    new OA\Property(property: 'reason', type: 'string', example: 'Khách hàng có hành vi đặt chuyến ảo nhiều lần.'),
                    new OA\Property(property: 'complaint_id', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Gửi cảnh báo thành công'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ hoặc thiếu thông tin'),
            new OA\Response(response: 404, description: 'Không tìm thấy Customer'),
        ]
    )]
    public function warnCustomer(\App\Modules\RiskManagement\Http\Requests\WarnCustomerRequest $request, string $userId): JsonResponse
    {
        $result = $this->violationService->warnUser(WarnUserDTO::fromRequest($request, $userId));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
