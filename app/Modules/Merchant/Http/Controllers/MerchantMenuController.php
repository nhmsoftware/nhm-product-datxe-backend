<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Merchant\DTO\GetMenuDTO;
use App\Modules\Merchant\Interfaces\MenuServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class MerchantMenuController extends BaseController
{
    public function __construct(
        private readonly MenuServiceInterface $menuService
    ) {}

    #[OA\Get(
        path: '/api/v1/merchant/menu',
        summary: 'Lấy danh sách thực đơn của cửa hàng (UC-57)',
        tags: ['Merchant'],
        parameters: [
            new OA\Parameter(
                name: 'category_id',
                in: 'query',
                description: 'Lọc theo ID danh mục',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Tìm kiếm tên món ăn',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'order', type: 'integer'),
                                    new OA\Property(
                                        property: 'items',
                                        type: 'array',
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: 'id', type: 'string'),
                                                new OA\Property(property: 'name', type: 'string'),
                                                new OA\Property(property: 'price', type: 'number', format: 'float'),
                                                new OA\Property(property: 'is_available', type: 'boolean'),
                                                new OA\Property(property: 'image_path', type: 'string', nullable: true),
                                            ]
                                        )
                                    ),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
        ]
    )]
    public function index(\App\Modules\Merchant\Http\Requests\GetMenuRequest $request): JsonResponse
    {
        $dto = GetMenuDTO::fromRequest($request);
        
        if (!$dto->merchantProfileId) {
            return $this->sendError('Tài khoản không phải là Merchant hoặc chưa có cửa hàng', 403);
        }

        $menu = $this->menuService->getMerchantMenu($dto);

        return $this->sendSuccess($menu);
    }

    #[OA\Post(
        path: '/api/v1/merchant/menu/items',
        summary: 'Thêm món ăn/đồ uống mới vào Menu (UC-58)',
        tags: ['Merchant'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name', 'price', 'category_name'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Cơm tấm Sườn Bì Chả'),
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 45000),
                        new OA\Property(property: 'category_name', type: 'string', example: 'Món chính'),
                        new OA\Property(property: 'category_id', type: 'string', description: 'ID danh mục nếu chọn từ list'),
                        new OA\Property(property: 'description', type: 'string', example: 'Sườn nướng mật ong thơm ngon'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Hình ảnh món ăn'),
                        new OA\Property(
                            property: 'sizes',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'name', type: 'string', example: 'Lớn'),
                                    new OA\Property(property: 'price', type: 'number', example: 10000),
                                    new OA\Property(property: 'is_default', type: 'boolean', example: false),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'toppings',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'name', type: 'string', example: 'Thêm sườn'),
                                    new OA\Property(property: 'price', type: 'number', example: 15000),
                                    new OA\Property(property: 'max_quantity', type: 'integer', example: 2),
                                    new OA\Property(property: 'is_required', type: 'boolean', example: false),
                                ]
                            )
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Thêm món ăn thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Thêm món ăn thành công.'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
            new OA\Response(response: 403, description: 'Không có quyền'),
        ]
    )]
    public function store(\App\Modules\Merchant\Http\Requests\CreateMenuItemRequest $request): JsonResponse
    {
        $dto = \App\Modules\Merchant\DTO\CreateMenuItemDTO::fromRequest($request);

        if (!$dto->merchantProfileId) {
            return $this->sendError('Tài khoản không phải là Merchant hoặc chưa có cửa hàng', 403);
        }

        $result = $this->menuService->createMenuItem($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
    }

    #[OA\Post(
        path: '/api/v1/merchant/menu/items/{id}',
        summary: 'Cập nhật thông tin món ăn (UC-59)',
        description: 'Sử dụng POST với tham số _method=PUT nếu cần giả lập PUT khi gửi kèm file.',
        tags: ['Merchant'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của món ăn cần cập nhật',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name', 'price', 'category_name'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Cơm tấm Sườn Bì Chả Đặc Biệt'),
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 55000),
                        new OA\Property(property: 'category_name', type: 'string', example: 'Món chính'),
                        new OA\Property(property: 'category_id', type: 'string', description: 'ID danh mục nếu chọn từ list'),
                        new OA\Property(property: 'description', type: 'string', example: 'Sườn nướng mật ong thơm ngon, bì thính vàng rụm'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Hình ảnh mới (nếu muốn thay đổi)'),
                        new OA\Property(
                            property: 'sizes',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'name', type: 'string', example: 'Lớn'),
                                    new OA\Property(property: 'price', type: 'number', example: 10000),
                                    new OA\Property(property: 'is_default', type: 'boolean', example: false),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'toppings',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'name', type: 'string', example: 'Thêm sườn'),
                                    new OA\Property(property: 'price', type: 'number', example: 15000),
                                    new OA\Property(property: 'max_quantity', type: 'integer', example: 2),
                                    new OA\Property(property: 'is_required', type: 'boolean', example: false),
                                ]
                            )
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cập nhật món ăn thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Cập nhật món ăn thành công.'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Không tìm thấy món ăn'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ hoặc tên món đã tồn tại'),
        ]
    )]
    public function update(\App\Modules\Merchant\Http\Requests\UpdateMenuItemRequest $request, string $id): JsonResponse
    {
        $dto = \App\Modules\Merchant\DTO\UpdateMenuItemDTO::fromRequest($request, $id);

        if (!$dto->merchantProfileId) {
            return $this->sendError('Tài khoản không phải là Merchant hoặc chưa có cửa hàng', 403);
        }

        $result = $this->menuService->updateMenuItem($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/merchant/menu/items/{id}',
        summary: 'Xóa món ăn khỏi thực đơn (Soft Delete) (UC-60)',
        tags: ['Merchant'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của món ăn cần xóa',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Xóa món ăn thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Xóa món ăn thành công.'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Không tìm thấy món ăn'),
            new OA\Response(response: 400, description: 'Không thể xóa món ăn (ví dụ: đang trong đơn hàng)'),
        ]
    )]
    public function delete(Request $request, string $id): JsonResponse
    {
        $dto = \App\Modules\Merchant\DTO\DeleteMenuItemDTO::fromRequest($request, $id);

        if (!$dto->merchantProfileId) {
            return $this->sendError('Tài khoản không phải là Merchant hoặc chưa có cửa hàng', 403);
        }

        $result = $this->menuService->deleteMenuItem($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess(null, $result->getMessage());
    }
}
