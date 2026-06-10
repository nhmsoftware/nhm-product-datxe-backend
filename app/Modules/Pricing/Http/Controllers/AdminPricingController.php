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
                    new OA\Property(property: 'vehicle_type_id', type: 'integer'),
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

    #[OA\Post(
        path: '/api/v1/admin/pricing/configs', 
        summary: 'Thiết lập cấu hình giá (UC-91, UC-125)', 
        description: 'Cho phép Admin cấu hình giá cho các loại dịch vụ, bao gồm dịch vụ Lái hộ (Chauffeur). Các trường bao gồm phí mở cửa, giá/km, giá/phút, giá tối thiểu và hệ số tăng giá.',
        security: [['bearerAuth' => []]],
        tags: ['Admin Pricing'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'vehicle_type_id', type: 'integer', example: 6, description: 'ID loại xe trong catalog'),
                    new OA\Property(property: 'base_price', type: 'number', example: 50000),
                    new OA\Property(property: 'distance_rate', type: 'number', example: 15000),
                    new OA\Property(property: 'time_rate', type: 'number', example: 1000),
                    new OA\Property(property: 'min_fare', type: 'number', example: 60000),
                    new OA\Property(property: 'surge_multiplier', type: 'number', example: 1.0),
                    new OA\Property(property: 'commission_rate', type: 'number', example: 25.0),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật cấu hình giá thành công'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ (A1)')
        ]
    )]
    public function updateConfig(ConfigurePricingRequest $request): JsonResponse
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

    #[OA\Get(
        path: '/api/v1/admin/pricing/history/{vehicleType}',
        summary: 'Lấy lịch sử thay đổi cấu hình giá (UC-125)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Pricing'],
        parameters: [new OA\Parameter(name: 'vehicleType', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Thành công')]
    )]
    public function getHistory(int $vehicleType): JsonResponse
    {
        $result = $this->pricingService->getPricingHistory($vehicleType);
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    public function resetToDefault(int $vehicleType): JsonResponse
    {
        $result = $this->pricingService->resetToDefault($vehicleType);
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        return $this->sendSuccess($result->getData(), 'Đặt lại cấu hình giá mặc định thành công.');
    }

    public function archiveConfig(int $vehicleTypeId): JsonResponse
    {
        $result = $this->pricingService->archiveConfig($vehicleTypeId);
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Đã lưu trữ cấu hình giá thành công.');
    }
}
