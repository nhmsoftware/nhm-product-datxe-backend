<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Pricing\DTO\ToggleFreeModeDTO;
use App\Modules\Pricing\DTO\UpdatePricingConfigDTO;
use App\Modules\Pricing\DTO\SurgeRuleDTO;
use App\Modules\Pricing\Http\Requests\ConfigurePricingRequest;
use App\Modules\Pricing\Http\Requests\ToggleFreeModeRequest;
use App\Modules\Pricing\Http\Requests\SetSurgePricingRequest;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class AdminPricingController extends BaseController
{
    public function __construct(
        private readonly PricingServiceInterface $pricingService,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/pricing/surge-rules',
        summary: 'Danh sách quy tắc giá cao điểm (UC-96)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Pricing'],
        responses: [new OA\Response(response: 200, description: 'Thành công')]
    )]
    public function listSurgeRules(): JsonResponse
    {
        $result = $this->pricingService->getSurgeRules();
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/admin/pricing/surge-rules',
        summary: 'Lưu quy tắc giá cao điểm (UC-96)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Pricing'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'vehicle_type', type: 'integer'),
                    new OA\Property(property: 'conditions', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'multiplier', type: 'number'),
                    new OA\Property(property: 'start_time', type: 'string', format: 'H:i'),
                    new OA\Property(property: 'end_time', type: 'string', format: 'H:i'),
                    new OA\Property(property: 'area_id', type: 'string'),
                    new OA\Property(property: 'rule_id', type: 'string'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Thành công')]
    )]
    public function saveSurgeRule(SetSurgePricingRequest $request): JsonResponse
    {
        $dto = SurgeRuleDTO::fromRequest($request);
        $result = $this->pricingService->saveSurgeRule($dto, $request->input('rule_id'));
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Cập nhật cấu hình giá giờ cao điểm thành công.');
    }

    #[OA\Delete(
        path: '/api/v1/admin/pricing/surge-rules/{ruleId}',
        summary: 'Xóa quy tắc giá cao điểm',
        security: [['bearerAuth' => []]],
        tags: ['Admin Pricing'],
        parameters: [new OA\Parameter(name: 'ruleId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'Thành công')]
    )]
    public function deleteSurgeRule(string $ruleId): JsonResponse
    {
        $result = $this->pricingService->deleteSurgeRule($ruleId);
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(path: '/api/v1/admin/pricing/configs', summary: 'Xem cấu hình giá (UC-91)', tags: ['Admin.Pricing'])]
    #[OA\Response(response: 200, description: 'Thành công')]
    public function getConfigs(): JsonResponse
    {
        $result = $this->pricingService->getConfigs();
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(path: '/api/v1/admin/pricing/configure', summary: 'Thiết lập giá (UC-91)', tags: ['Admin.Pricing'])]
    #[OA\Response(response: 200, description: 'Cập nhật thành công')]
    public function configure(ConfigurePricingRequest $request): JsonResponse
    {
        $result = $this->pricingService->updateConfig(UpdatePricingConfigDTO::fromRequest($request));
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        return $this->sendSuccess($result->getData(), 'Cập nhật cấu hình giá thành công.');
    }

    #[OA\Post(path: '/api/v1/admin/pricing/toggle-free-mode', summary: 'Bật/tắt chế độ miễn phí (UC-91)', tags: ['Admin.Pricing'])]
    #[OA\Response(response: 200, description: 'Cập nhật thành công')]
    public function toggleFreeMode(ToggleFreeModeRequest $request): JsonResponse
    {
        $result = $this->pricingService->toggleFreeMode(ToggleFreeModeDTO::fromRequest($request));
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        return $this->sendSuccess($result->getData(), 'Cập nhật chế độ miễn phí thành công.');
    }

    public function resetToDefault(int $vehicleType): JsonResponse
    {
        $result = $this->pricingService->resetToDefault($vehicleType);
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        return $this->sendSuccess($result->getData(), 'Đặt lại cấu hình giá mặc định thành công.');
    }
}
