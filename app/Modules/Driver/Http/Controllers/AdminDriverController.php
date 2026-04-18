<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Driver\DTO\ApproveRegistrationDTO;
use App\Modules\Driver\Http\Requests\ApproveRegistrationRequest;
use App\Modules\Driver\Interfaces\DriverRegistrationServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * @OA\Tag(name="Admin|Driver", description="Quản lý tài xế từ phía Admin")
 */
final class AdminDriverController extends BaseController
{
    public function __construct(
        private readonly DriverRegistrationServiceInterface $driverRegistrationService,
    ) {}

    /**
     * Duyệt hồ sơ đăng ký tài xế.
     */
    #[OA\Post(
        path: '/api/v1/admin/driver/applications/{id}/approve',
        summary: 'Duyệt hồ sơ tài xế',
        security: [['sanctum' => []]],
        tags: ['Admin|Driver'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID của hồ sơ cần duyệt',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: '1')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Duyệt thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy hồ sơ'),
            new OA\Response(response: 400, description: 'Hồ sơ không ở trạng thái chờ duyệt'),
            new OA\Response(response: 403, description: 'Không có quyền'),
        ]
    )]
    public function approve(ApproveRegistrationRequest $request): JsonResponse
    {
        $result = $this->driverRegistrationService->approveRegistration(
            ApproveRegistrationDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
