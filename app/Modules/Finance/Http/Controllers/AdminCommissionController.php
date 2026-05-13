<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Finance\DTO\ConfigureCommissionDTO;
use App\Modules\Finance\Http\Requests\ConfigureCommissionRequest;
use App\Modules\Finance\Interfaces\CommissionRuleServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class AdminCommissionController extends BaseController
{
    public function __construct(
        private readonly CommissionRuleServiceInterface $commissionRuleService
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/finance/commissions',
        summary: 'Danh sách cấu hình hoa hồng (UC-119)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Finance'],
        responses: [new OA\Response(response: 200, description: 'Thành công')]
    )]
    public function index(): JsonResponse
    {
        $result = $this->commissionRuleService->listConfigs();
        return $this->sendSuccess($result->getData(), 'Tải danh sách cấu hình hoa hồng thành công.');
    }

    #[OA\Post(
        path: '/api/v1/admin/finance/commissions',
        summary: 'Tạo cấu hình hoa hồng mới (UC-119)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Finance'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Hoa hồng Ride HCM'),
                    new OA\Property(property: 'target_type', type: 'integer', example: 1, description: '1: Driver, 2: Merchant'),
                    new OA\Property(property: 'service_type', type: 'integer', example: 1, description: '1: Ride, 2: Food, 3: Delivery'),
                    new OA\Property(property: 'scope', type: 'integer', example: 2, description: '1: System, 2: Regional'),
                    new OA\Property(property: 'area_id', type: 'string', example: 'hcm_area_01'),
                    new OA\Property(property: 'commission_rate', type: 'number', example: 20.0),
                    new OA\Property(property: 'min_commission', type: 'number', example: 5000),
                    new OA\Property(property: 'max_commission', type: 'number', example: 50000),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'effective_from', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'effective_to', type: 'string', format: 'date-time'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Thành công')]
    )]
    public function store(ConfigureCommissionRequest $request): JsonResponse
    {
        $result = $this->commissionRuleService->configure(ConfigureCommissionDTO::fromRequest($request));
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Cập nhật cấu hình hoa hồng thành công.', 201);
    }

    #[OA\Delete(
        path: '/api/v1/admin/finance/commissions/{id}',
        summary: 'Xóa cấu hình hoa hồng',
        security: [['bearerAuth' => []]],
        tags: ['Admin Finance'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'Thành công')]
    )]
    public function destroy(string $id): JsonResponse
    {
        $result = $this->commissionRuleService->deleteRule($id);
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Xóa cấu hình hoa hồng thành công.');
    }
}
