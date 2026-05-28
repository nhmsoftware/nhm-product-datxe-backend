<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Marketing\DTO\CreateBannerDTO;
use App\Modules\Marketing\DTO\UpdateBannerDTO;
use App\Modules\Marketing\Http\Requests\CreateBannerRequest;
use App\Modules\Marketing\Http\Requests\UpdateBannerRequest;
use App\Modules\Marketing\Interfaces\BannerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminBannerController extends BaseController
{
    public function __construct(
        protected BannerServiceInterface $bannerService
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/marketing/banners',
        summary: 'Lấy danh sách Banner',
        security: [['sanctum' => []]],
        tags: ['Admin Marketing Banners'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $result = $this->bannerService->getList($perPage);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy danh sách Banner thành công');
    }

    #[OA\Get(
        path: '/api/v1/admin/marketing/banners/{id}',
        summary: 'Lấy chi tiết Banner',
        security: [['sanctum' => []]],
        tags: ['Admin Marketing Banners'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy Banner')
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->bannerService->getDetail($id);
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy chi tiết Banner thành công');
    }

    #[OA\Post(
        path: '/api/v1/admin/marketing/banners',
        summary: 'Tạo mới Banner',
        security: [['sanctum' => []]],
        tags: ['Admin Marketing Banners'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['image'],
                    properties: [
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Hình ảnh Banner (Max 5MB)'),
                        new OA\Property(property: 'title', type: 'string', description: 'Tiêu đề Banner'),
                        new OA\Property(property: 'description', type: 'string', description: 'Mô tả Banner'),
                        new OA\Property(property: 'label', type: 'string', description: 'Nhãn Banner'),
                        new OA\Property(property: 'tag', type: 'string', description: 'Thẻ Banner'),
                        new OA\Property(property: 'action_url', type: 'string', description: 'Đường dẫn khi click'),
                        new OA\Property(property: 'order', type: 'integer', description: 'Thứ tự hiển thị', default: 0),
                        new OA\Property(property: 'status', type: 'integer', description: 'Trạng thái (1: Active, 2: Inactive)', default: 1),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tạo thành công'),
            new OA\Response(response: 422, description: 'Lỗi validate dữ liệu')
        ]
    )]
    public function store(CreateBannerRequest $request): JsonResponse
    {
        $dto = CreateBannerDTO::fromRequest($request);
        $result = $this->bannerService->create($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Tạo Banner thành công');
    }

    #[OA\Post(
        path: '/api/v1/admin/marketing/banners/{id}',
        summary: 'Cập nhật Banner (Hỗ trợ multipart/form-data via POST)',
        security: [['sanctum' => []]],
        tags: ['Admin Marketing Banners'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: '_method', type: 'string', example: 'PUT', description: 'Gửi _method=PUT khi form-data'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Hình ảnh Banner mới'),
                        new OA\Property(property: 'title', type: 'string', description: 'Tiêu đề Banner'),
                        new OA\Property(property: 'description', type: 'string', description: 'Mô tả Banner'),
                        new OA\Property(property: 'label', type: 'string', description: 'Nhãn Banner'),
                        new OA\Property(property: 'tag', type: 'string', description: 'Thẻ Banner'),
                        new OA\Property(property: 'action_url', type: 'string', description: 'Đường dẫn khi click'),
                        new OA\Property(property: 'order', type: 'integer', description: 'Thứ tự hiển thị'),
                        new OA\Property(property: 'status', type: 'integer', description: 'Trạng thái (1: Active, 2: Inactive)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy Banner')
        ]
    )]
    public function update(string $id, UpdateBannerRequest $request): JsonResponse
    {
        $dto = UpdateBannerDTO::fromRequest($request);
        $result = $this->bannerService->update($id, $dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Cập nhật Banner thành công');
    }

    #[OA\Delete(
        path: '/api/v1/admin/marketing/banners/{id}',
        summary: 'Xóa Banner',
        security: [['sanctum' => []]],
        tags: ['Admin Marketing Banners'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xóa thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy Banner')
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $result = $this->bannerService->delete($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Xóa Banner thành công');
    }
}
