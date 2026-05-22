<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Marketing\DTO\CreateNewsDTO;
use App\Modules\Marketing\DTO\UpdateNewsDTO;
use App\Modules\Marketing\Http\Requests\CreateNewsRequest;
use App\Modules\Marketing\Http\Requests\UpdateNewsRequest;
use App\Modules\Marketing\Interfaces\NewsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminNewsController extends BaseController
{
    public function __construct(
        protected NewsServiceInterface $newsService
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/marketing/news',
        summary: 'Lấy danh sách Tin tức',
        security: [['sanctum' => []]],
        tags: ['Admin Marketing News'],
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
        $result = $this->newsService->getList($perPage);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy danh sách Tin tức thành công');
    }

    #[OA\Get(
        path: '/api/v1/admin/marketing/news/{id}',
        summary: 'Lấy chi tiết Tin tức',
        security: [['sanctum' => []]],
        tags: ['Admin Marketing News'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy Tin tức')
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->newsService->getDetail($id);
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy chi tiết Tin tức thành công');
    }

    #[OA\Post(
        path: '/api/v1/admin/marketing/news',
        summary: 'Tạo mới Tin tức',
        security: [['sanctum' => []]],
        tags: ['Admin Marketing News'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['image', 'title'],
                    properties: [
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Hình ảnh Tin tức (Max 5MB)'),
                        new OA\Property(property: 'title', type: 'string', description: 'Tiêu đề Tin tức'),
                        new OA\Property(property: 'description', type: 'string', description: 'Mô tả ngắn'),
                        new OA\Property(property: 'content', type: 'string', description: 'Nội dung chi tiết (HTML/Text)'),
                        new OA\Property(property: 'tag', type: 'string', description: 'Thẻ phân loại (vd: Khuyến mãi, Tin mới)'),
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
    public function store(CreateNewsRequest $request): JsonResponse
    {
        $dto = CreateNewsDTO::fromRequest($request);
        $result = $this->newsService->create($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Tạo Tin tức thành công');
    }

    #[OA\Post(
        path: '/api/v1/admin/marketing/news/{id}',
        summary: 'Cập nhật Tin tức (Hỗ trợ multipart/form-data via POST)',
        security: [['sanctum' => []]],
        tags: ['Admin Marketing News'],
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
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Hình ảnh Tin tức mới'),
                        new OA\Property(property: 'title', type: 'string', description: 'Tiêu đề Tin tức'),
                        new OA\Property(property: 'description', type: 'string', description: 'Mô tả ngắn'),
                        new OA\Property(property: 'content', type: 'string', description: 'Nội dung chi tiết (HTML/Text)'),
                        new OA\Property(property: 'tag', type: 'string', description: 'Thẻ phân loại'),
                        new OA\Property(property: 'order', type: 'integer', description: 'Thứ tự hiển thị'),
                        new OA\Property(property: 'status', type: 'integer', description: 'Trạng thái (1: Active, 2: Inactive)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy Tin tức')
        ]
    )]
    public function update(string $id, UpdateNewsRequest $request): JsonResponse
    {
        $dto = UpdateNewsDTO::fromRequest($request);
        $result = $this->newsService->update($id, $dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Cập nhật Tin tức thành công');
    }

    #[OA\Delete(
        path: '/api/v1/admin/marketing/news/{id}',
        summary: 'Xóa Tin tức',
        security: [['sanctum' => []]],
        tags: ['Admin Marketing News'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xóa thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy Tin tức')
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $result = $this->newsService->delete($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Xóa Tin tức thành công');
    }
}
