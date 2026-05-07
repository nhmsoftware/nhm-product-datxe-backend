<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\RiskManagement\DTO\ListFraudAlertsDTO;
use App\Modules\RiskManagement\Http\Requests\ListFraudAlertsRequest;
use App\Modules\RiskManagement\Interfaces\AntiFraudServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Controller quản lý hệ thống chống gian lận dành cho Admin.
 */
final class AdminAntiFraudController extends BaseController
{
    public function __construct(
        private readonly AntiFraudServiceInterface $antiFraudService,
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/risk/anti-fraud/overview',
        summary: 'Tổng quan hệ thống chống gian lận (UC-104)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Risk Management'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Không có quyền'),
        ]
    )]
    public function overview(): JsonResponse
    {
        $result = $this->antiFraudService->getOverview();
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/risk/anti-fraud/alerts',
        summary: 'Danh sách cảnh báo gian lận (UC-104)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Risk Management'],
        parameters: [
            new OA\Parameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'target_type', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'risk_level', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'fraud_type', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Không có quyền'),
        ]
    )]
    public function listAlerts(ListFraudAlertsRequest $request): JsonResponse
    {
        $result = $this->antiFraudService->listAlerts(ListFraudAlertsDTO::fromRequest($request));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData()->toArray(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/risk/anti-fraud/alerts/{id}',
        summary: 'Chi tiết cảnh báo gian lận (UC-104)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Risk Management'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(string|int $id): JsonResponse
    {
        $result = $this->antiFraudService->getDetail($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
