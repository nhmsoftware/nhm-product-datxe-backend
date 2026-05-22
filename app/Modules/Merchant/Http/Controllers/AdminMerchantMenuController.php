<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Merchant\DTO\AdminCreateMenuItemDTO;
use App\Modules\Merchant\DTO\AdminUpdateMenuItemDTO;
use App\Modules\Merchant\Http\Requests\Admin\AdminCreateMenuItemRequest;
use App\Modules\Merchant\Http\Requests\Admin\AdminUpdateMenuItemRequest;
use App\Modules\Merchant\Http\Requests\Admin\AdminImportMenuRequest;
use App\Modules\Merchant\Interfaces\AdminMenuServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

final class AdminMerchantMenuController extends BaseController
{
    public function __construct(
        private readonly AdminMenuServiceInterface $adminMenuService
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/merchant/{merchantId}/menu',
        summary: 'Lấy thực đơn của cửa hàng dành cho Admin',
        tags: ['Admin Merchant Menu'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'merchantId',
                in: 'path',
                required: true,
                description: 'ID của Merchant Profile',
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
        ]
    )]
    public function index(string $merchantId): JsonResponse
    {
        $menu = $this->adminMenuService->getMerchantMenu($merchantId);
        return $this->sendSuccess($menu);
    }

    #[OA\Get(
        path: '/api/v1/admin/merchant/{merchantId}/menu/categories',
        summary: 'Lấy danh sách danh mục của cửa hàng (chỉ categories) - Admin',
        tags: ['Admin Merchant Menu'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'merchantId',
                in: 'path',
                required: true,
                description: 'ID của Merchant Profile',
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
        ]
    )]
    public function categories(string $merchantId): JsonResponse
    {
        $categories = $this->adminMenuService->getMerchantCategories($merchantId);
        return $this->sendSuccess($categories);
    }

    #[OA\Post(
        path: '/api/v1/admin/merchant/{merchantId}/menu/items',
        summary: 'Admin thêm món ăn mới',
        tags: ['Admin Merchant Menu'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'merchantId',
                in: 'path',
                required: true,
                description: 'ID của Merchant Profile',
                schema: new OA\Schema(type: 'string')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name', 'price', 'category_name'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Cơm sườn đặc biệt'),
                        new OA\Property(property: 'price', type: 'number', example: 50000),
                        new OA\Property(property: 'category_name', type: 'string', example: 'Món chính'),
                        new OA\Property(property: 'category_id', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tạo thành công'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function store(AdminCreateMenuItemRequest $request, string $merchantId): JsonResponse
    {
        $dto = AdminCreateMenuItemDTO::fromRequest($request, $merchantId);
        $result = $this->adminMenuService->createMenuItem($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
    }

    #[OA\Post(
        path: '/api/v1/admin/merchant/{merchantId}/menu/items/{itemId}',
        summary: 'Admin cập nhật món ăn',
        description: 'Sử dụng POST với tham số _method=PUT nếu cần giả lập PUT khi gửi kèm file.',
        tags: ['Admin Merchant Menu'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'merchantId',
                in: 'path',
                required: true,
                description: 'ID của Merchant Profile',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'itemId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name', 'price', 'category_name'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'price', type: 'number'),
                        new OA\Property(property: 'category_name', type: 'string'),
                        new OA\Property(property: 'category_id', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy món ăn'),
        ]
    )]
    public function update(AdminUpdateMenuItemRequest $request, string $merchantId, string $itemId): JsonResponse
    {
        $dto = AdminUpdateMenuItemDTO::fromRequest($request, $itemId, $merchantId);
        $result = $this->adminMenuService->updateMenuItem($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/admin/merchant/{merchantId}/menu/items/{itemId}',
        summary: 'Admin xóa món ăn',
        tags: ['Admin Merchant Menu'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'merchantId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'itemId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xóa thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function delete(Request $request, string $merchantId, string $itemId): JsonResponse
    {
        $actorId = (string) $request->user()->id;
        $result = $this->adminMenuService->deleteMenuItem($itemId, $merchantId, $actorId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess(null, $result->getMessage());
    }

    #[OA\Patch(
        path: '/api/v1/admin/merchant/{merchantId}/menu/items/{itemId}/status',
        summary: 'Admin thay đổi trạng thái bán của món ăn',
        tags: ['Admin Merchant Menu'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'merchantId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'itemId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['is_available'],
                properties: [
                    new OA\Property(property: 'is_available', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function updateStatus(Request $request, string $merchantId, string $itemId): JsonResponse
    {
        $request->validate(['is_available' => 'required|boolean']);

        $actorId = (string) $request->user()->id;
        $isAvailable = (bool) $request->input('is_available');

        $result = $this->adminMenuService->updateMenuItemStatus($itemId, $merchantId, $isAvailable, $actorId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess(null, $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/merchant/{merchantId}/menu/logs',
        summary: 'Lấy nhật ký chỉnh sửa thực đơn',
        tags: ['Admin Merchant Menu'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'merchantId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function logs(string $merchantId): JsonResponse
    {
        $result = $this->adminMenuService->getEditLogs($merchantId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData());
    }

    #[OA\Get(
        path: '/api/v1/admin/merchant/menu/export-template',
        summary: 'Xuất file Excel mẫu để nhập thực đơn',
        tags: ['Admin Merchant Menu'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Thành công - tải file Excel mẫu (.xlsx)'),
        ]
    )]
    public function exportTemplate(): Response
    {
        $xlsxContent = $this->adminMenuService->exportTemplate();

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="menu_template.xlsx"',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/merchant/{merchantId}/menu/import',
        summary: 'Admin nhập thực đơn từ file CSV mẫu',
        tags: ['Admin Merchant Menu'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'merchantId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Nhập thành công'),
            new OA\Response(response: 400, description: 'Lỗi định dạng file'),
        ]
    )]
    public function import(AdminImportMenuRequest $request, string $merchantId): JsonResponse
    {
        $file = $request->file('file');
        $actorId = (string) $request->user()->id;

        $result = $this->adminMenuService->importMenu($merchantId, $file, $actorId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
