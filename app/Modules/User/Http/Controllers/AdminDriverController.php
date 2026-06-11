<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\User\DTO\Admin\CreateDriverDTO;
use App\Modules\User\DTO\Admin\ListDriversDTO;
use App\Modules\User\DTO\Admin\ApproveDriverDTO;
use App\Modules\User\DTO\Admin\RejectDriverDTO;
use App\Modules\User\DTO\Admin\UpdateDriverDTO;
use App\Modules\User\DTO\Admin\UpdateDriverStatusDTO;
use App\Modules\User\DTO\Admin\AssignDriverGroupDTO;
use App\Modules\User\Http\Requests\Admin\CreateDriverRequest;
use App\Modules\User\Http\Requests\Admin\ListDriversRequest;
use App\Modules\User\Http\Requests\Admin\ApproveDriverRequest;
use App\Modules\User\Http\Requests\Admin\RejectDriverRequest;
use App\Modules\User\Http\Requests\Admin\UpdateDriverRequest;
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

        return $this->sendSuccess($result->getData()->toArray(), $result->getMessage());

    }

    #[OA\Post(
        path: '/api/v1/admin/drivers',
        summary: 'Tạo tài xế (UC-144)',
        description: 'Tạo mới tài khoản tài xế trên Admin Portal.',
        security: [['bearerAuth' => []]],
        tags: ['Admin Driver Management'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['full_name', 'phone'],
                properties: [
                    new OA\Property(property: 'full_name', type: 'string', example: 'Nguyen Van Tai'),
                    new OA\Property(property: 'phone', type: 'string', example: '0900000012'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'driver@example.com'),
                    new OA\Property(property: 'gender', type: 'integer', example: 1),
                    new OA\Property(property: 'birthday', type: 'string', format: 'date', example: '1995-04-12'),
                    new OA\Property(property: 'address', type: 'string', example: '12 Cach Mang Thang 8, Q3, TP.HCM'),
                    new OA\Property(property: 'driver_group_type', type: 'integer', example: 1, description: '1: Xe nha, 2: Doi tac'),
                    new OA\Property(property: 'vehicle_type_id', type: 'integer', example: 1),
                    new OA\Property(property: 'vehicle_color', type: 'integer', example: 1),
                    new OA\Property(property: 'vehicle_name', type: 'string', example: 'Honda Vision'),
                    new OA\Property(property: 'vehicle_number', type: 'string', example: '59A1-12345'),
                    new OA\Property(property: 'password', type: 'string', example: 'Password@123'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'note', type: 'string', example: 'Tạo nhanh từ admin'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tạo tài xế thành công'),
            new OA\Response(response: 400, description: 'Lỗi validation'),
            new OA\Response(response: 409, description: 'Số điện thoại hoặc email đã tồn tại'),
        ]
    )]
    public function store(CreateDriverRequest $request): JsonResponse
    {
        $result = $this->adminDriverService->createDriver(CreateDriverDTO::fromRequest($request));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
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
        path: '/api/v1/admin/drivers/{userId}',
        summary: 'Cập nhật tài xế (UC-145)',
        description: 'Cập nhật thông tin hồ sơ tài xế.',
        security: [['bearerAuth' => []]],
        tags: ['Admin Driver Management'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, description: 'ID tài xế', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['full_name', 'phone'],
                properties: [
                    new OA\Property(property: 'full_name', type: 'string', example: 'Nguyen Van Tai'),
                    new OA\Property(property: 'phone', type: 'string', example: '0900000012'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'driver@example.com'),
                    new OA\Property(property: 'gender', type: 'integer', example: 1),
                    new OA\Property(property: 'birthday', type: 'string', format: 'date', example: '1995-04-12'),
                    new OA\Property(property: 'address', type: 'string', example: '12 Cach Mang Thang 8, Q3, TP.HCM'),
                    new OA\Property(property: 'driver_group_type', type: 'integer', example: 1),
                    new OA\Property(property: 'vehicle_type_id', type: 'integer', example: 1),
                    new OA\Property(property: 'vehicle_color', type: 'integer', example: 1),
                    new OA\Property(property: 'vehicle_name', type: 'string', example: 'Honda Vision'),
                    new OA\Property(property: 'vehicle_number', type: 'string', example: '59A1-12345'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'kyc_status', type: 'integer', example: 1, description: '0: Chua nop, 1: Cho duyet, 2: Da duyet, 3: Tu choi'),
                    new OA\Property(property: 'lock_reason', type: 'string', example: 'Tam khoa do vi pham'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật thành công'),
            new OA\Response(response: 400, description: 'Lỗi validation'),
            new OA\Response(response: 404, description: 'Không tìm thấy tài xế'),
        ]
    )]
    public function update(string|int $userId, UpdateDriverRequest $request): JsonResponse
    {
        $result = $this->adminDriverService->updateDriver(UpdateDriverDTO::fromRequest($request, $userId));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/admin/drivers/{userId}',
        summary: 'Xóa tài xế (UC-145)',
        description: 'Xóa mềm tài khoản tài xế nếu không có chuyến/đơn đang xử lý.',
        security: [['bearerAuth' => []]],
        tags: ['Admin Driver Management'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, description: 'ID tài xế', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xóa thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy tài xế'),
            new OA\Response(response: 409, description: 'Tài xế đang có chuyến/đơn đang xử lý'),
        ]
    )]
    public function destroy(string|int $userId): JsonResponse
    {
        $result = $this->adminDriverService->deleteDriver($userId);

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

    #[OA\Get(
        path: '/api/v1/admin/drivers/export',
        summary: 'Xuất Excel tài xế',
        description: 'Xuất danh sách tài xế theo bộ lọc hiện tại',
        security: [['bearerAuth' => []]],
        tags: ['Admin Driver Management'],
        parameters: [
            new OA\Parameter(name: 'keyword', in: 'query', description: 'Từ khóa tìm kiếm', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'kyc_status', in: 'query', description: 'Trạng thái duyệt', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', description: 'Trạng thái hoạt động', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function export(ListDriversRequest $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $result = $this->adminDriverService->exportDrivers(ListDriversDTO::fromRequest($request));
        
        if ($result->isError()) {
            abort($result->getCode(), $result->getMessage());
        }

        $data = $result->getData()['items'];

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            
            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            if (!empty($data)) {
                $headerMap = [
                    'id' => 'ID',
                    'full_name' => 'Họ và tên',
                    'phone' => 'Số điện thoại',
                    'email' => 'Email',
                    'kyc_status' => 'Trạng thái duyệt',
                    'is_active' => 'Hoạt động',
                    'driver_group_type' => 'Loại đội xe',
                    'created_at' => 'Ngày tạo',
                    'updated_at' => 'Ngày cập nhật',
                    'status' => 'Trạng thái',
                    'avatar' => 'Ảnh đại diện',
                    'citizen_id' => 'CCCD/CMND'
                ];
                $headers = array_keys($data[0]);
                $translatedHeaders = array_map(function($key) use ($headerMap) {
                    return $headerMap[$key] ?? $key;
                }, $headers);
                fputcsv($handle, $translatedHeaders);
            }

            foreach ($data as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        }, 'danh_sach_tai_xe_' . now()->getTimestamp() . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
