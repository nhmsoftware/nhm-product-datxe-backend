<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Merchant\DTO\CreateMerchantDTO;
use App\Modules\Merchant\DTO\MerchantFilterDTO;
use App\Modules\Merchant\DTO\UpdateMerchantDTO;
use App\Modules\Merchant\Http\Requests\Admin\CreateMerchantRequest;
use App\Modules\Merchant\Http\Requests\Admin\MerchantListRequest;
use App\Modules\Merchant\Http\Requests\Admin\UpdateMerchantRequest;
use App\Modules\Merchant\Interfaces\MerchantAdminServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * @OA\Tag(name="Admin|Merchant", description="Quản lý Merchant từ phía Admin")
 */
final class AdminMerchantController extends BaseController
{
    public function __construct(
        private readonly MerchantAdminServiceInterface $merchantAdminService,
    ) {}

    /**
     * Danh sách Merchant (UC-86)
     */
    #[OA\Get(
        path: '/api/v1/admin/merchant',
        summary: 'Danh sách Merchant',
        security: [['sanctum' => []]],
        tags: ['Admin|Merchant'],
        parameters: [
            new OA\Parameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Tìm theo tên, SĐT, Email'),
            new OA\Parameter(name: 'store_name', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'owner_name', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'phone', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'email', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer', description: '1: Pending, 2: Approved, 3: Rejected')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function index(MerchantListRequest $request): JsonResponse
    {
        $result = $this->merchantAdminService->getMerchants(MerchantFilterDTO::fromRequest($request));
        return $this->sendSuccess($result->getData());
    }

    #[OA\Post(
        path: '/api/v1/admin/merchant',
        summary: 'Tạo Merchant (UC-146)',
        security: [['sanctum' => []]],
        tags: ['Admin|Merchant'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['owner_name', 'phone', 'store_name', 'store_address'],
                    properties: [
                        new OA\Property(property: 'owner_name', type: 'string', example: 'Nguyen Van Chu'),
                        new OA\Property(property: 'phone', type: 'string', example: '0900000099'),
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'store_name', type: 'string', example: 'Com Nha Tot'),
                        new OA\Property(property: 'store_address', type: 'string', example: '123 Nguyen Hue, Q1'),
                        new OA\Property(property: 'latitude', type: 'number', format: 'float'),
                        new OA\Property(property: 'longitude', type: 'number', format: 'float'),
                        new OA\Property(property: 'business_type', type: 'integer', example: 1),
                        new OA\Property(property: 'business_license', type: 'string', example: 'GPKD-123'),
                        new OA\Property(property: 'business_license_image', type: 'string', format: 'binary'),
                        new OA\Property(property: 'store_image', type: 'string', format: 'binary'),
                        new OA\Property(property: 'opening_time', type: 'string', example: '08:00'),
                        new OA\Property(property: 'closing_time', type: 'string', example: '22:00'),
                        new OA\Property(property: 'registered_at', type: 'string', format: 'date'),
                        new OA\Property(property: 'status', type: 'integer', example: 1),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'password', type: 'string', example: 'Password@123'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tạo Merchant thành công'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ'),
            new OA\Response(response: 409, description: 'Số điện thoại hoặc tên cửa hàng đã tồn tại'),
        ]
    )]
    public function store(CreateMerchantRequest $request): JsonResponse
    {
        $result = $this->merchantAdminService->createMerchant(CreateMerchantDTO::fromRequest($request));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
    }

    /**
     * Chi tiết Merchant (UC-90)
     */
    #[OA\Get(
        path: '/api/v1/admin/merchant/{id}',
        summary: 'Chi tiết Merchant',
        security: [['sanctum' => []]],
        tags: ['Admin|Merchant'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'merchant', properties: [
                            new OA\Property(property: 'id', type: 'string'),
                            new OA\Property(property: 'store_name', type: 'string'),
                            new OA\Property(property: 'store_address', type: 'string'),
                            new OA\Property(property: 'business_type', type: 'integer', enum: [1, 2, 3, 4, 5, 6, 7, 8]),
                            new OA\Property(property: 'status', description: 'Trạng thái duyệt', type: 'integer'),
                            new OA\Property(property: 'business_license', type: 'string'),
                            new OA\Property(property: 'business_license_image', type: 'string'),
                            new OA\Property(property: 'user', properties: [
                                new OA\Property(property: 'id', type: 'string'),
                                new OA\Property(property: 'phone', type: 'string'),
                                new OA\Property(property: 'email', type: 'string'),
                                new OA\Property(property: 'is_active', type: 'boolean'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'customer_profile', properties: [
                                    new OA\Property(property: 'full_name', type: 'string'),
                                ], type: 'object')
                            ], type: 'object')
                        ], type: 'object'),
                        new OA\Property(property: 'menu', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'string'),
                                new OA\Property(property: 'merchant_profile_id', type: 'string'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'order', type: 'integer'),
                                new OA\Property(property: 'is_active', type: 'boolean'),
                                new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string'),
                                        new OA\Property(property: 'category_id', type: 'string'),
                                        new OA\Property(property: 'name', type: 'string'),
                                        new OA\Property(property: 'description', type: 'string'),
                                        new OA\Property(property: 'price', type: 'number', format: 'float'),
                                        new OA\Property(property: 'image_path', type: 'string'),
                                        new OA\Property(property: 'is_available', type: 'boolean'),
                                        new OA\Property(property: 'order', type: 'integer'),
                                        new OA\Property(property: 'rating', type: 'number', format: 'float'),
                                    ],
                                    type: 'object'
                                ))
                            ],
                            type: 'object'
                        ))
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Merchant không tồn tại.'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->merchantAdminService->getMerchantDetails($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData());
    }

    #[OA\Post(
        path: '/api/v1/admin/merchant/{id}',
        summary: 'Cập nhật Merchant (UC-147)',
        security: [['sanctum' => []]],
        tags: ['Admin|Merchant'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['owner_name', 'phone', 'store_name', 'store_address'],
                    properties: [
                        new OA\Property(property: 'owner_name', type: 'string'),
                        new OA\Property(property: 'phone', type: 'string'),
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'store_name', type: 'string'),
                        new OA\Property(property: 'store_address', type: 'string'),
                        new OA\Property(property: 'latitude', type: 'number', format: 'float'),
                        new OA\Property(property: 'longitude', type: 'number', format: 'float'),
                        new OA\Property(property: 'business_type', type: 'integer'),
                        new OA\Property(property: 'business_license', type: 'string'),
                        new OA\Property(property: 'business_license_image', type: 'string', format: 'binary'),
                        new OA\Property(property: 'store_image', type: 'string', format: 'binary'),
                        new OA\Property(property: 'opening_time', type: 'string'),
                        new OA\Property(property: 'closing_time', type: 'string'),
                        new OA\Property(property: 'status', type: 'integer'),
                        new OA\Property(property: 'is_active', type: 'boolean'),
                        new OA\Property(property: 'lock_reason', type: 'string'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy Merchant'),
        ]
    )]
    public function update(UpdateMerchantRequest $request, string $id): JsonResponse
    {
        $result = $this->merchantAdminService->updateMerchant(UpdateMerchantDTO::fromRequest($request, $id));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/admin/merchant/{id}',
        summary: 'Xóa Merchant (UC-147)',
        security: [['sanctum' => []]],
        tags: ['Admin|Merchant'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xóa thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy Merchant'),
            new OA\Response(response: 409, description: 'Merchant đang có đơn xử lý'),
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $result = $this->merchantAdminService->deleteMerchant($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    /**
     * Duyệt Merchant (UC-86)
     */
    #[OA\Post(
        path: '/api/v1/admin/merchant/{id}/approve',
        summary: 'Duyệt Merchant',
        security: [['sanctum' => []]],
        tags: ['Admin|Merchant'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Duyệt thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function approve(string $id): JsonResponse
    {
        $result = $this->merchantAdminService->approveMerchant($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Duyệt Merchant thành công.');
    }

    /**
     * Từ chối Merchant (UC-86)
     */
    #[OA\Post(
        path: '/api/v1/admin/merchant/{id}/reject',
        summary: 'Từ chối Merchant',
        security: [['sanctum' => []]],
        tags: ['Admin|Merchant'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', example: 'Tài liệu không rõ ràng')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Từ chối thành công'),
        ]
    )]
    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate(
            ['reason' => 'required|string'],
            ['reason.required' => 'Vui lòng nhập lý do từ chối.']
        );

        $result = $this->merchantAdminService->rejectMerchant($id, $request->input('reason'));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Từ chối Merchant thành công.');
    }

    /**
     * Khóa/Mở khóa Merchant (UC-89)
     */
    #[OA\Post(
        path: '/api/v1/admin/merchant/{id}/toggle-lock',
        summary: 'Khóa/Mở khóa Merchant',
        security: [['sanctum' => []]],
        tags: ['Admin|Merchant'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['lock'],
                properties: [
                    new OA\Property(property: 'lock', type: 'boolean', example: true, description: 'true để khóa, false để mở khóa'),
                    new OA\Property(property: 'reason', type: 'string', example: 'Vi phạm điều khoản', description: 'Bắt buộc khi khóa'),
                    new OA\Property(property: 'locked_days', type: 'integer', example: 7, description: 'Số ngày khóa, mặc định 2'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function toggleLock(Request $request, string $id): JsonResponse
    {
        $isLock = $request->boolean('lock');

        if ($isLock) {
            $request->validate([
                'reason'      => 'required|string',
                'locked_days' => 'nullable|integer|min:2'
            ], [
                'reason.required'    => 'Vui lòng nhập lý do khóa tài khoản.',
                'locked_days.integer' => 'Số ngày khóa không hợp lệ.',
                'locked_days.min'     => 'Số ngày khóa không hợp lệ.',
            ]);
        }

        $result = $this->merchantAdminService->toggleLockMerchant(
            $id,
            $isLock,
            $request->input('reason'),
            $request->has('locked_days') ? (int) $request->input('locked_days') : null
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        $message = $isLock ? 'Khóa tài khoản Merchant thành công.' : 'Mở khóa tài khoản Merchant thành công.';
        return $this->sendSuccess($result->getData(), $message);
    }
}
