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
}
