<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Finance\DTO\AdminDriverFinanceSummaryDTO;
use App\Modules\Finance\Interfaces\AdminDriverFinanceServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class AdminDriverFinanceController extends BaseController
{
    public function __construct(
        private readonly AdminDriverFinanceServiceInterface $adminDriverFinanceService
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/finance/driver-summary',
        summary: 'Tổng quan tài chính tài xế (UC-116)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Finance'],
        parameters: [
            new OA\Parameter(
                name: 'start_date',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'end_date',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_drivers', type: 'integer', example: 150),
                        new OA\Property(property: 'total_drivers_internal', type: 'integer', example: 50),
                        new OA\Property(property: 'total_drivers_partner', type: 'integer', example: 100),
                        new OA\Property(property: 'total_revenue', type: 'number', example: 150000000),
                        new OA\Property(property: 'total_commission', type: 'number', example: 30000000),
                        new OA\Property(property: 'total_drivers_blocked', type: 'integer', example: 5),
                        new OA\Property(property: 'currency', type: 'string', example: 'VND'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
        ]
    )]
    public function summary(Request $request): JsonResponse
    {
        $result = $this->adminDriverFinanceService->getSummary(
            AdminDriverFinanceSummaryDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Tải dữ liệu tổng quan tài chính tài xế thành công.');
    }

    /**
     * UC-116 Extended: Báo cáo Tài chính chi tiết theo tháng.
     * GET /api/v1/admin/finance/reports
     */
    public function reports(Request $request): JsonResponse
    {
        $result = $this->adminDriverFinanceService->getReports(
            AdminDriverFinanceSummaryDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Tải báo cáo tài chính chi tiết thành công.');
    }
}
