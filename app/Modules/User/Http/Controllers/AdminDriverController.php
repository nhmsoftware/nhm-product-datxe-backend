<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\User\DTO\Admin\ListDriversDTO;
use App\Modules\User\DTO\Admin\ApproveDriverDTO;
use App\Modules\User\DTO\Admin\RejectDriverDTO;
use App\Modules\User\DTO\Admin\UpdateDriverStatusDTO;
use App\Modules\User\DTO\Admin\AssignDriverGroupDTO;
use App\Modules\User\Http\Requests\Admin\ListDriversRequest;
use App\Modules\User\Http\Requests\Admin\ApproveDriverRequest;
use App\Modules\User\Http\Requests\Admin\RejectDriverRequest;
use App\Modules\User\Http\Requests\Admin\UpdateDriverStatusRequest;
use App\Modules\User\Http\Requests\Admin\AssignDriverGroupRequest;
use App\Modules\User\Model\Enums\DriverGroupType;
use App\Modules\User\Interfaces\AdminDriverServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class AdminDriverController extends BaseController
{
    public function __construct(
        private readonly AdminDriverServiceInterface $adminDriverService,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/drivers',
        summary: 'Danh sách tài xế (UC-80)',
        description: 'Lấy danh sách tài xế có phân trang và tìm kiếm',
        security: [['bearerAuth' => []]],
        tags: ['Admin Driver Management'],
        parameters: [
            new OA\Parameter(name: 'keyword', in: 'query', description: 'Từ khóa tìm kiếm (Tên, SĐT, Email)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'kyc_status', in: 'query', description: 'Trạng thái duyệt (1: Chờ duyệt, 2: Đã duyệt, 3: Từ chối)', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', description: 'Trạng thái hoạt động', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Số lượng trên mỗi trang', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Trang hiện tại', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Không có quyền'),
        ]
    )]
    public function listDrivers(ListDriversRequest $request): JsonResponse
    {
        $result = $this->adminDriverService->listDrivers(ListDriversDTO::fromRequest($request));
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/admin/drivers/{userId}/approve',
        summary: 'Duyệt tài xế (UC-81)',
        description: 'Duyệt hồ sơ đăng ký tài xế',
        security: [['bearerAuth' => []]],
        tags: ['Admin Driver Management'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, description: 'ID người dùng', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'note', type: 'string', example: 'Hồ sơ đầy đủ'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function approve(string|int $userId, ApproveDriverRequest $request): JsonResponse
    {
        $dto = new ApproveDriverDTO(
            userId: $userId,
            note: $request->input('note')
        );

        $result = $this->adminDriverService->approveDriver($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/admin/drivers/{userId}/reject',
        summary: 'Từ chối tài xế (UC-82)',
        description: 'Từ chối hồ sơ đăng ký tài xế kèm lý do',
        security: [['bearerAuth' => []]],
        tags: ['Admin Driver Management'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, description: 'ID người dùng', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'reason', type: 'string', example: 'Hình ảnh không rõ nét'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function reject(string|int $userId, RejectDriverRequest $request): JsonResponse
    {
        $dto = new RejectDriverDTO(
            userId: $userId,
            reason: $request->input('reason')
        );

        $result = $this->adminDriverService->rejectDriver($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/drivers/{userId}',
        summary: 'Chi tiết tài xế (UC-83)',
        description: 'Xem thông tin chi tiết của một tài xế',
        security: [['bearerAuth' => []]],
        tags: ['Admin Driver Management'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, description: 'ID người dùng', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(string|int $userId): JsonResponse
    {
        $result = $this->adminDriverService->getDriverDetail($userId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/admin/drivers/{userId}/status',
        summary: 'Khóa/Mở khóa tài xế (UC-84)',
        description: 'Cập nhật trạng thái hoạt động của tài xế',
        security: [['bearerAuth' => []]],
        tags: ['Admin Driver Management'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, description: 'ID người dùng', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'is_active', type: 'boolean', example: false),
                    new OA\Property(property: 'lock_reason', type: 'string', example: 'Vi phạm quy định hệ thống'),
                    new OA\Property(property: 'locked_days', type: 'integer', example: 7),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function updateStatus(string|int $userId, UpdateDriverStatusRequest $request): JsonResponse
    {
        $dto = new UpdateDriverStatusDTO(
            userId: $userId,
            isActive: $request->boolean('is_active'),
            lockReason: $request->input('lock_reason'),
            lockedDays: $request->input('locked_days')
        );

        $result = $this->adminDriverService->updateStatus($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/admin/drivers/{userId}/assign-group',
        summary: 'Gán đội xe (UC-85)',
        description: 'Gán tài xế vào đội xe nhà hoặc đối tác',
        security: [['bearerAuth' => []]],
        tags: ['Admin Driver Management'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, description: 'ID người dùng', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'group_type', type: 'integer', example: 1, description: '1: Đội xe nhà, 2: Tài xế đối tác'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function assignGroup(string|int $userId, AssignDriverGroupRequest $request): JsonResponse
    {
        $dto = new AssignDriverGroupDTO(
            userId: $userId,
            groupType: DriverGroupType::from($request->input('group_type'))
        );

        $result = $this->adminDriverService->assignDriverGroup($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
