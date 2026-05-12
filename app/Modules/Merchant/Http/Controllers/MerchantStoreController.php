<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Merchant\Interfaces\MerchantStoreServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class MerchantStoreController extends BaseController
{
    public function __construct(
        private readonly MerchantStoreServiceInterface $storeService
    ) {}

    #[OA\Get(path: '/api/v1/merchant/store', summary: 'Lấy thông tin cửa hàng (UC-53)', tags: ['Merchant'])]
    #[OA\Response(response: 200, description: 'Thành công')]
    public function getInfo(Request $request): JsonResponse
    {
        $result = $this->storeService->getStoreInfo((string)$request->user()->id);
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(path: '/api/v1/merchant/store/status', summary: 'Cập nhật trạng thái đóng/mở (UC-46)', tags: ['Merchant'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['is_open'],
            properties: [
                new OA\Property(property: 'is_open', type: 'boolean', example: true)
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Cập nhật thành công')]
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate(['is_open' => ['required', 'boolean']]);
        $result = $this->storeService->updateStatus((string)$request->user()->id, (bool)$request->input('is_open'));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Cập nhật trạng thái thành công.');
    }

    #[OA\Put(path: '/api/v1/merchant/store/hours', summary: 'Thiết lập giờ mở cửa (Cơ bản) (UC-45)', tags: ['Merchant'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['opening_time', 'closing_time'],
            properties: [
                new OA\Property(property: 'opening_time', type: 'string', example: '08:00'),
                new OA\Property(property: 'closing_time', type: 'string', example: '22:00'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Cập nhật thành công')]
    public function updateHours(Request $request): JsonResponse
    {
        $request->validate([
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i'],
        ]);
        $result = $this->storeService->updateOperatingHours(
            (string)$request->user()->id,
            $request->input('opening_time'),
            $request->input('closing_time')
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Cập nhật giờ hoạt động thành công.');
    }

    #[OA\Put(path: '/api/v1/merchant/store/weekly-hours', summary: 'Thiết lập giờ mở cửa chi tiết theo tuần (UC-54)', tags: ['Merchant'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['schedule'],
            properties: [
                new OA\Property(
                    property: 'schedule',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'day_of_week', type: 'integer', example: 1),
                            new OA\Property(property: 'is_closed', type: 'boolean', example: false),
                            new OA\Property(property: 'opening_time', type: 'string', example: '08:00'),
                            new OA\Property(property: 'closing_time', type: 'string', example: '22:00'),
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Cập nhật thành công')]
    public function updateWeeklyHours(Request $request): JsonResponse
    {
        $request->validate([
            'schedule'              => ['required', 'array', 'min:1', 'max:7'],
            'schedule.*.day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'schedule.*.is_closed'   => ['required', 'boolean'],
            'schedule.*.opening_time'=> ['nullable', 'date_format:H:i', 'required_if:schedule.*.is_closed,false'],
            'schedule.*.closing_time'=> ['nullable', 'date_format:H:i', 'required_if:schedule.*.is_closed,false'],
        ]);

        $result = $this->storeService->updateWeeklySchedule(
            (string)$request->user()->id,
            $request->input('schedule')
        );

        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Cập nhật giờ hoạt động hàng tuần thành công.');
    }

    #[OA\Put(path: '/api/v1/merchant/store/discount', summary: 'Cấu hình chiết khấu (Cơ bản) (UC-47)', tags: ['Merchant'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['commission_rate'],
            properties: [
                new OA\Property(property: 'commission_rate', type: 'number', format: 'float', example: 20.5)
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Cập nhật thành công')]
    public function updateDiscount(Request $request): JsonResponse
    {
        $request->validate(['commission_rate' => ['required', 'numeric', 'min:0', 'max:100']]);
        $result = $this->storeService->updateDiscount(
            (string)$request->user()->id,
            (float)$request->input('commission_rate')
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Cập nhật chiết khấu thành công.');
    }

    #[OA\Get(path: '/api/v1/merchant/store/commission-packages', summary: 'Lấy danh sách gói chiết khấu (UC-56)', tags: ['Merchant'])]
    #[OA\Response(response: 200, description: 'Thành công')]
    public function getPackages(): JsonResponse
    {
        $packages = $this->storeService->getCommissionPackages();
        return $this->sendSuccess($packages, 'Tải danh sách gói chiết khấu thành công.');
    }

    #[OA\Put(path: '/api/v1/merchant/store/commission-package', summary: 'Thay đổi gói chiết khấu (UC-56)', tags: ['Merchant'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['package_key'],
            properties: [
                new OA\Property(property: 'package_key', type: 'string', example: 'PRIORITY')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Cập nhật thành công')]
    public function updatePackage(Request $request): JsonResponse
    {
        $request->validate([
            'package_key' => ['required', 'string'],
        ]);

        $result = $this->storeService->updateCommissionPackage(
            (string)$request->user()->id,
            $request->input('package_key')
        );

        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/merchant/store/stats/orders',
        summary: 'Xem thống kê số lượng đơn hàng (UC-66)',
        security: [['sanctum' => []]],
        tags: ['Merchant'],
        parameters: [
            new OA\Parameter(
                name: 'period',
                in: 'query',
                description: 'Khoảng thời gian (today, week, month)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['today', 'week', 'month'], default: 'today')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_orders', type: 'integer', example: 15),
                        new OA\Property(property: 'period', type: 'string', example: 'today'),
                        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-05-08'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Không tìm thấy cửa hàng')
        ]
    )]
    public function getOrderStats(\App\Modules\Merchant\Http\Requests\GetOrderStatsRequest $request): JsonResponse
    {
        $result = $this->storeService->getOrderStats(
            (string)$request->user()->id,
            $request->query('period', 'today')
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/merchant/store/stats/revenue',
        summary: 'Xem thống kê doanh thu (UC-67)',
        security: [['sanctum' => []]],
        tags: ['Merchant'],
        parameters: [
            new OA\Parameter(
                name: 'period',
                in: 'query',
                description: 'Khoảng thời gian (today, week, month)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['today', 'week', 'month'], default: 'today')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_revenue', type: 'number', format: 'float', example: 1500000.5),
                        new OA\Property(property: 'period', type: 'string', example: 'today'),
                        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-05-08'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Không tìm thấy cửa hàng')
        ]
    )]
    public function getRevenueStats(\App\Modules\Merchant\Http\Requests\GetOrderStatsRequest $request): JsonResponse
    {
        $result = $this->storeService->getRevenueStats(
            (string)$request->user()->id,
            $request->query('period', 'today')
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/merchant/store/stats/average-order-value',
        summary: 'Xem giá trị trung bình đơn hàng (UC-67.a)',
        security: [['sanctum' => []]],
        tags: ['Merchant'],
        parameters: [
            new OA\Parameter(
                name: 'period',
                in: 'query',
                description: 'Khoảng thời gian (today, week, month)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['today', 'week', 'month'], default: 'today')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'average_order_value', type: 'number', format: 'float', example: 150000.5),
                        new OA\Property(property: 'total_revenue', type: 'number', format: 'float', example: 1500000.0),
                        new OA\Property(property: 'total_orders', type: 'integer', example: 10),
                        new OA\Property(property: 'period', type: 'string', example: 'today'),
                        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-05-08'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Không tìm thấy cửa hàng')
        ]
    )]
    public function getAverageOrderValue(\App\Modules\Merchant\Http\Requests\GetOrderStatsRequest $request): JsonResponse
    {
        $result = $this->storeService->getAverageOrderValue(
            (string)$request->user()->id,
            $request->query('period', 'today')
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/merchant/store/stats/revenue-chart',
        summary: 'Xem biểu đồ doanh thu (UC-67.b)',
        security: [['sanctum' => []]],
        tags: ['Merchant'],
        parameters: [
            new OA\Parameter(
                name: 'period',
                in: 'query',
                description: 'Khoảng thời gian (today, week, month)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['today', 'week', 'month'], default: 'today')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'chart_data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'label', type: 'string', example: '10:00 hoặc 12/05'),
                                    new OA\Property(property: 'revenue', type: 'number', format: 'float', example: 500000.0)
                                ]
                            )
                        ),
                        new OA\Property(property: 'period', type: 'string', example: 'today'),
                        new OA\Property(property: 'message', type: 'string', example: '')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Không tìm thấy cửa hàng')
        ]
    )]
    public function getRevenueChart(\App\Modules\Merchant\Http\Requests\GetOrderStatsRequest $request): JsonResponse
    {
        $result = $this->storeService->getRevenueChart(
            (string)$request->user()->id,
            $request->query('period', 'today')
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/merchant/store/stats/recent-transactions',
        summary: 'Xem giao dịch gần đây (UC-67.c)',
        security: [['sanctum' => []]],
        tags: ['Merchant'],
        parameters: [
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Số lượng giao dịch (mặc định 5)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 5)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'transaction_id', type: 'string', example: 'TX123456'),
                            new OA\Property(property: 'time', type: 'string', format: 'date-time'),
                            new OA\Property(property: 'amount', type: 'number', format: 'float', example: 150000.0),
                            new OA\Property(property: 'payment_method', type: 'string', example: 'Ví hệ thống'),
                            new OA\Property(property: 'status', type: 'string', example: 'Thành công'),
                            new OA\Property(property: 'description', type: 'string'),
                            new OA\Property(property: 'type', type: 'string')
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: 'Không tìm thấy cửa hàng')
        ]
    )]
    public function getRecentTransactions(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 5);
        $result = $this->storeService->getRecentTransactions(
            (string)$request->user()->id,
            $limit
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
