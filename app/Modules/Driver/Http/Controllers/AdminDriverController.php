<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Driver\DTO\AdminSubmitDriverRegistrationDTO;
use App\Modules\Driver\DTO\ApproveRegistrationDTO;
use App\Modules\Driver\Http\Requests\AdminSubmitDriverRegistrationRequest;
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
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['driver_group_id'],
                properties: [
                    new OA\Property(property: 'driver_group_id', description: 'ID nhóm tài xế: 1 - Đội xe nhà, 2 - Đội xe đối tác', type: 'number', example: 1),
                ]
            )
        ),
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

    /**
     * Danh sách hồ sơ đang chờ duyệt.
     */
    #[OA\Get(
        path: '/api/v1/admin/driver/applications',
        summary: 'Danh sách hồ sơ chờ duyệt',
        security: [['sanctum' => []]],
        tags: ['Admin|Driver'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function index(): JsonResponse
    {
        $result = $this->driverRegistrationService->getApplications();
        return $this->sendSuccess($result->getData());
    }

    /**
     * Chi tiết hồ sơ đăng ký.
     */
    #[OA\Get(
        path: '/api/v1/admin/driver/applications/{id}',
        summary: 'Chi tiết hồ sơ đăng ký tài xế',
        security: [['sanctum' => []]],
        tags: ['Admin|Driver'],
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
        $result = $this->driverRegistrationService->getApplicationDetails($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData());
    }

    /**
     * Danh sách đội xe nhà (Driver Groups).
     */
    #[OA\Get(
        path: '/api/v1/admin/driver/groups',
        summary: 'Danh sách đội xe',
        security: [['sanctum' => []]],
        tags: ['Admin|Driver'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function groups(): JsonResponse
    {
        $result = $this->driverRegistrationService->getDriverGroups();
        return $this->sendSuccess($result->getData());
    }

    #[OA\Post(
        path: '/api/v1/admin/driver/users/{userId}/register-submit',
        summary: 'Admin upload hồ sơ KYC cho tài xế',
        security: [['sanctum' => []]],
        tags: ['Admin|Driver'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['full_name', 'phone', 'citizen_id', 'vehicle_type', 'vehicle_name', 'vehicle_color', 'vehicle_number', 'vehicle_year', 'services', 'cccd_front', 'cccd_back', 'driver_license', 'vehicle_reg', 'criminal_record', 'health_cert', 'portrait', 'insurance'],
                    properties: [
                        new OA\Property(property: 'full_name', type: 'string'),
                        new OA\Property(property: 'phone', type: 'string'),
                        new OA\Property(property: 'citizen_id', type: 'string'),
                        new OA\Property(property: 'vehicle_type', type: 'integer'),
                        new OA\Property(property: 'vehicle_name', type: 'string'),
                        new OA\Property(property: 'vehicle_color', type: 'integer'),
                        new OA\Property(property: 'vehicle_number', type: 'string'),
                        new OA\Property(property: 'vehicle_year', type: 'integer'),
                        new OA\Property(property: 'services', type: 'array', items: new OA\Items(type: 'integer')),
                        new OA\Property(property: 'cccd_front', type: 'string', format: 'binary'),
                        new OA\Property(property: 'cccd_back', type: 'string', format: 'binary'),
                        new OA\Property(property: 'driver_license', type: 'string', format: 'binary'),
                        new OA\Property(property: 'vehicle_reg', type: 'string', format: 'binary'),
                        new OA\Property(property: 'criminal_record', type: 'string', format: 'binary'),
                        new OA\Property(property: 'health_cert', type: 'string', format: 'binary'),
                        new OA\Property(property: 'portrait', type: 'string', format: 'binary'),
                        new OA\Property(property: 'insurance', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tạo hồ sơ KYC thành công'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function submitForUser(AdminSubmitDriverRegistrationRequest $request, string $userId): JsonResponse
    {
        $result = $this->driverRegistrationService->submitRegistrationByAdmin(
            AdminSubmitDriverRegistrationDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
