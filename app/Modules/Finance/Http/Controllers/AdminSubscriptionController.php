<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Finance\DTO\CreateSubscriptionPackageDTO;
use App\Modules\Finance\DTO\UpdateSubscriptionPackageDTO;
use App\Modules\Finance\Http\Requests\AdminCreateSubscriptionPackageRequest;
use App\Modules\Finance\Http\Requests\AdminUpdateSubscriptionPackageRequest;
use App\Modules\Finance\Interfaces\AdminSubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class AdminSubscriptionController extends BaseController
{
    public function __construct(
        private readonly AdminSubscriptionServiceInterface $adminSubscriptionService
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/finance/subscriptions/packages',
        summary: 'Danh sách tất cả gói thuê bao (Admin) (UC-118)',
        security: [['bearerAuth' => []]],
        tags: ['Admin - Finance'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Gói tháng Tiêu chuẩn'),
                                    new OA\Property(property: 'package_type', type: 'string', example: 'monthly'),
                                    new OA\Property(property: 'price', type: 'number', example: 300000),
                                    new OA\Property(property: 'duration_days', type: 'integer', example: 30),
                                    new OA\Property(property: 'service_fee_reduction_percent', type: 'number', example: 100),
                                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                ]
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(): JsonResponse
    {
        $result = $this->adminSubscriptionService->listPackages();
        return $this->sendSuccess($result->getData(), 'Lấy danh sách gói thuê bao thành công.');
    }

    #[OA\Post(
        path: '/api/v1/admin/finance/subscriptions/packages',
        summary: 'Tạo gói thuê bao mới (Admin) (UC-118)',
        security: [['bearerAuth' => []]],
        tags: ['Admin - Finance'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'package_type', 'price', 'duration_days'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Gói tháng Tiêu chuẩn'),
                    new OA\Property(property: 'package_type', type: 'string', enum: ['daily', 'weekly', 'monthly'], example: 'monthly'),
                    new OA\Property(property: 'price', type: 'number', example: 300000),
                    new OA\Property(property: 'duration_days', type: 'integer', example: 30),
                    new OA\Property(property: 'service_fee_reduction_percent', type: 'number', example: 100),
                    new OA\Property(property: 'description', type: 'string', example: 'Nhận 100% cước, không hoa hồng trong 30 ngày'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tạo thành công'),
            new OA\Response(response: 400, description: 'Vui lòng nhập đầy đủ thông tin gói thuê bao.'),
            new OA\Response(response: 422, description: 'Gói thuê bao này đã tồn tại.'),
        ]
    )]
    public function store(AdminCreateSubscriptionPackageRequest $request): JsonResponse
    {
        $result = $this->adminSubscriptionService->createPackage(
            CreateSubscriptionPackageDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Cấu hình gói thuê bao thành công.', 201);
    }

    #[OA\Put(
        path: '/api/v1/admin/finance/subscriptions/packages/{id}',
        summary: 'Cập nhật gói thuê bao (Admin) (UC-118)',
        security: [['bearerAuth' => []]],
        tags: ['Admin - Finance'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'package_type', 'price', 'duration_days'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Gói tháng Premium'),
                    new OA\Property(property: 'package_type', type: 'string', enum: ['daily', 'weekly', 'monthly'], example: 'monthly'),
                    new OA\Property(property: 'price', type: 'number', example: 500000),
                    new OA\Property(property: 'duration_days', type: 'integer', example: 30),
                    new OA\Property(property: 'service_fee_reduction_percent', type: 'number', example: 100),
                    new OA\Property(property: 'description', type: 'string', example: 'Nhận 100% cước trong 30 ngày'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy gói thuê bao.'),
            new OA\Response(response: 422, description: 'Gói thuê bao này đã tồn tại.'),
            new OA\Response(response: 500, description: 'Không thể cập nhật Subscription Package.'),
        ]
    )]
    public function update(AdminUpdateSubscriptionPackageRequest $request, string $id): JsonResponse
    {
        $result = $this->adminSubscriptionService->updatePackage(
            $id,
            UpdateSubscriptionPackageDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Cấu hình gói thuê bao thành công.');
    }

    #[OA\Patch(
        path: '/api/v1/admin/finance/subscriptions/packages/{id}/disable',
        summary: 'Vô hiệu hóa gói thuê bao (Admin) (UC-118 - A5)',
        security: [['bearerAuth' => []]],
        tags: ['Admin - Finance'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Vô hiệu hóa thành công. Tài xế đang dùng vẫn có hiệu lực đến ngày hết hạn.'),
            new OA\Response(response: 404, description: 'Không tìm thấy gói thuê bao.'),
            new OA\Response(response: 409, description: 'Gói thuê bao này đã bị vô hiệu hóa.'),
        ]
    )]
    public function disable(string $id): JsonResponse
    {
        $result = $this->adminSubscriptionService->disablePackage($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getData()['message'] ?? 'Vô hiệu hóa thành công.');
    }
}
