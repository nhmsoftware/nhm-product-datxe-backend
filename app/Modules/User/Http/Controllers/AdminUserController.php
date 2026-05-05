<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\User\DTO\Admin\ListUsersDTO;
use App\Modules\User\DTO\Admin\UpdateUserStatusDTO;
use App\Modules\User\Http\Requests\Admin\ListUsersRequest;
use App\Modules\User\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Modules\User\Interfaces\AdminUserServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class AdminUserController extends BaseController
{
    public function __construct(
        private readonly AdminUserServiceInterface $adminUserService,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/users/customers',
        summary: 'Danh sách khách hàng (UC-77)',
        description: 'Lấy danh sách khách hàng có phân trang và tìm kiếm',
        security: [['bearerAuth' => []]],
        tags: ['Admin User Management'],
        parameters: [
            new OA\Parameter(name: 'keyword', in: 'query', description: 'Từ khóa tìm kiếm (Tên, SĐT, Email)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', description: 'Trạng thái hoạt động', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Số lượng trên mỗi trang', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Trang hiện tại', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Không có quyền'),
        ]
    )]
    public function listCustomers(ListUsersRequest $request): JsonResponse
    {
        $result = $this->adminUserService->listCustomers(ListUsersDTO::fromRequest($request));
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/users/{userId}',
        summary: 'Chi tiết người dùng (UC-79)',
        description: 'Xem thông tin chi tiết của một tài khoản Customer',
        security: [['bearerAuth' => []]],
        tags: ['Admin User Management'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, description: 'ID người dùng', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(string|int $userId): JsonResponse
    {
        $result = $this->adminUserService->getCustomerDetail($userId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/admin/users/{userId}/status',
        summary: 'Khóa/Mở khóa người dùng (UC-69/UC-77)',
        description: 'Cập nhật trạng thái hoạt động của người dùng',
        security: [['bearerAuth' => []]],
        tags: ['Admin User Management'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, description: 'ID người dùng', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'is_active', type: 'boolean', example: false),
                    new OA\Property(property: 'reason', type: 'string', description: 'Lý do khóa (bắt buộc nếu is_active=false)', example: 'Vi phạm điều khoản'),
                    new OA\Property(property: 'locked_days', type: 'integer', description: 'Số ngày khóa (mặc định 2)', example: 7),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 400, description: 'Lỗi validation'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function updateStatus(string|int $userId, UpdateUserStatusRequest $request): JsonResponse
    {
        $result = $this->adminUserService->updateUserStatus(UpdateUserStatusDTO::fromRequest($request, $userId));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
