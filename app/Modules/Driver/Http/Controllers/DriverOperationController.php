<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Driver\DTO\ToggleOnlineStatusDTO;
use App\Modules\Driver\Http\Requests\ToggleOnlineStatusRequest;
use App\Modules\Driver\Interfaces\DriverOperationServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class DriverOperationController extends BaseController
{
    public function __construct(
        private readonly DriverOperationServiceInterface $driverOperationService,
    ) {}

    #[OA\Put(
        path: '/api/v1/driver/status',
        summary: 'UC-31: Cập nhật trạng thái Go Online / Go Offline',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['is_online'],
                properties: [
                    new OA\Property(property: 'is_online', type: 'boolean', example: true, description: 'True để Online, False để Offline'),
                    new OA\Property(property: 'current_lat', type: 'number', format: 'float', example: 10.776889, description: 'Bắt buộc nếu is_online = true'),
                    new OA\Property(property: 'current_lng', type: 'number', format: 'float', example: 106.700806, description: 'Bắt buộc nếu is_online = true'),
                ]
            )
        ),
        tags: ['Driver'],
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật thành công'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ'),
            new OA\Response(response: 403, description: 'Tài khoản chưa được duyệt hoặc bị khóa'),
            new OA\Response(response: 422, description: 'Không thể cập nhật khi đang có chuyến'),
        ]
    )]
    public function toggleStatus(ToggleOnlineStatusRequest $request): JsonResponse
    {
        $result = $this->driverOperationService->toggleOnlineStatus(
            ToggleOnlineStatusDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
