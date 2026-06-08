<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\User\DTO\Admin\CreateCustomerDTO;
use App\Modules\User\DTO\Admin\ListUsersDTO;
use App\Modules\User\DTO\Admin\UpdateCustomerDTO;
use App\Modules\User\DTO\Admin\UpdateUserStatusDTO;
use App\Modules\User\Http\Requests\Admin\CreateCustomerRequest;
use App\Modules\User\Http\Requests\Admin\ListUsersRequest;
use App\Modules\User\Http\Requests\Admin\UpdateCustomerRequest;
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
        security: [['sanctum' => []]],
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

        return $this->sendSuccess($result->getData()->toArray(), $result->getMessage());

    }

    #[OA\Get(
        path: '/api/v1/admin/users/{userId}',
        summary: 'Chi tiết người dùng (UC-79)',
        description: 'Xem thông tin chi tiết của một tài khoản Customer',
        security: [['sanctum' => []]],
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
        path: '/api/v1/admin/users/{userId}',
        summary: 'Cập nhật khách hàng (UC-143)',
        description: 'Cho phép Admin cập nhật thông tin hồ sơ khách hàng.',
        security: [['sanctum' => []]],
        tags: ['Admin User Management'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, description: 'ID khách hàng', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['full_name', 'phone'],
                properties: [
                    new OA\Property(property: 'full_name', type: 'string', example: 'Nguyen Van B'),
                    new OA\Property(property: 'phone', type: 'string', example: '0900000009'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'updated@example.com'),
                    new OA\Property(property: 'gender', type: 'integer', example: 2),
                    new OA\Property(property: 'birthday', type: 'string', format: 'date', example: '1997-01-15'),
                    new OA\Property(property: 'address', type: 'string', example: '456 Le Loi, Quan 1, TP.HCM'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật thành công'),
            new OA\Response(response: 400, description: 'Lỗi validation'),
            new OA\Response(response: 404, description: 'Không tìm thấy khách hàng'),
            new OA\Response(response: 409, description: 'Số điện thoại hoặc email đã được sử dụng'),
        ]
    )]
    public function update(string|int $userId, UpdateCustomerRequest $request): JsonResponse
    {
        $result = $this->adminUserService->updateCustomer(UpdateCustomerDTO::fromRequest($request, $userId));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/admin/users/{userId}/status',
        summary: 'Khóa/Mở khóa người dùng (UC-78)',
        description: 'Cập nhật trạng thái hoạt động của người dùng',
        security: [['sanctum' => []]],
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
                    new OA\Property(property: 'locked_days', type: 'integer', description: 'Số ngày khóa (3 hoặc 7)', example: 7),
                    new OA\Property(property: 'lock_expired_at', type: 'string', format: 'date', description: 'Ngày hết hạn khóa cụ thể (YYYY-MM-DD)', example: '2026-12-31'),
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

    #[OA\Delete(
        path: '/api/v1/admin/users/{userId}',
        summary: 'Xóa khách hàng (UC-143)',
        description: 'Cho phép Admin xóa mềm khách hàng nếu không có đơn/chuyến đang xử lý.',
        security: [['sanctum' => []]],
        tags: ['Admin User Management'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, description: 'ID khách hàng', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xóa thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy khách hàng'),
            new OA\Response(response: 409, description: 'Khách hàng đang có đơn hoặc chuyến đang xử lý'),
        ]
    )]
    public function destroy(string|int $userId): JsonResponse
    {
        $result = $this->adminUserService->deleteCustomer($userId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/admin/users/customers',
        summary: 'Tạo mới khách hàng (UC-142)',
        description: 'Cho phép Admin tạo mới tài khoản khách hàng trên Admin Portal.',
        security: [['sanctum' => []]],
        tags: ['Admin User Management'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['full_name', 'phone'],
                properties: [
                    new OA\Property(property: 'full_name', type: 'string', example: 'Nguyen Van A'),
                    new OA\Property(property: 'phone', type: 'string', example: '0900000002'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'customer@example.com'),
                    new OA\Property(property: 'gender', type: 'integer', example: 1, description: '1: Nam, 2: Nu, 3: Khac'),
                    new OA\Property(property: 'birthday', type: 'string', format: 'date', example: '1998-10-20'),
                    new OA\Property(property: 'address', type: 'string', example: '123 Nguyen Trai, Quan 1, TP.HCM'),
                    new OA\Property(property: 'password', type: 'string', example: 'Password@123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tạo khách hàng thành công'),
            new OA\Response(response: 400, description: 'Lỗi validation'),
            new OA\Response(response: 409, description: 'Số điện thoại hoặc email đã tồn tại'),
        ],
    )]
    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $result = $this->adminUserService->createCustomer(CreateCustomerDTO::fromRequest($request));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Tạo khách hàng thành công.', 201);
    }
}
