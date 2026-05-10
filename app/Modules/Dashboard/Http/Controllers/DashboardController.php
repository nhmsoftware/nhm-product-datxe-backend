<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Dashboard\Interfaces\DashboardServiceInterface;
use App\Modules\Dashboard\Http\Requests\GetDashboardRequest;
use Illuminate\Http\JsonResponse;

use OpenApi\Attributes as OA;

final class DashboardController extends BaseController
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboardService,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/dashboard',
        summary: 'Lấy dữ liệu thống kê Dashboard',
        description: 'Cho phép Admin xem tổng quan tình trạng hoạt động của hệ thống (UC-76)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Dashboard'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'total_users', type: 'integer', example: 1500),
                                new OA\Property(property: 'total_orders', type: 'integer', example: 5000),
                                new OA\Property(property: 'total_revenue', type: 'number', format: 'float', example: 120000000),
                                new OA\Property(property: 'active_merchants', type: 'integer', example: 45),
                                new OA\Property(property: 'active_drivers', type: 'integer', example: 120),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Không có quyền truy cập'
            ),
        ]
    )]
    public function getStats(GetDashboardRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getDashboardStats();

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/dashboard/revenue',
        summary: 'Báo cáo doanh thu',
        security: [['bearerAuth' => []]],
        tags: ['Admin Dashboard'],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'interval', in: 'query', schema: new OA\Schema(type: 'string', enum: ['day', 'month', 'year'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function getRevenueReport(\App\Modules\Dashboard\Http\Requests\DashboardReportRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getRevenueReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO::fromRequest($request));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/dashboard/area',
        summary: 'Báo cáo theo khu vực',
        security: [['bearerAuth' => []]],
        tags: ['Admin Dashboard'],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function getAreaReport(\App\Modules\Dashboard\Http\Requests\DashboardReportRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getAreaReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO::fromRequest($request));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/dashboard/commission',
        summary: 'Báo cáo hoa hồng',
        security: [['bearerAuth' => []]],
        tags: ['Admin Dashboard'],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function getCommissionReport(\App\Modules\Dashboard\Http\Requests\DashboardReportRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getCommissionReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO::fromRequest($request));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/dashboard/orders',
        summary: 'Báo cáo quản lý đơn hàng',
        security: [['bearerAuth' => []]],
        tags: ['Admin Dashboard'],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function getOrderReport(\App\Modules\Dashboard\Http\Requests\DashboardReportRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getOrderReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO::fromRequest($request));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/dashboard/detailed',
        summary: 'Báo cáo chi tiết loại xe và dịch vụ',
        security: [['bearerAuth' => []]],
        tags: ['Admin Dashboard'],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function getDetailedReport(\App\Modules\Dashboard\Http\Requests\DashboardReportRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getDetailedReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO::fromRequest($request));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/dashboard/top-drivers',
        summary: 'Báo cáo top tài xế theo doanh thu',
        security: [['bearerAuth' => []]],
        tags: ['Admin Dashboard'],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function getTopDriversReport(\App\Modules\Dashboard\Http\Requests\DashboardReportRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getTopDriversReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO::fromRequest($request));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
