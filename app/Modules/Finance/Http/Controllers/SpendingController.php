<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Finance\DTO\ViewSpendingSummaryDTO;
use App\Modules\Finance\Http\Requests\ViewSpendingSummaryRequest;
use App\Modules\Finance\Interfaces\SpendingServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class SpendingController extends BaseController
{
    public function __construct(
        private readonly SpendingServiceInterface $spendingService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/finance/spending-summary',
        description: 'Lấy thông tin tổng hợp chi tiêu của khách hàng hiện tại theo khoảng thời gian (ngày/tuần/tháng/tùy chọn)',
        summary: 'Xem thống kê chi tiêu (UC-23)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\Parameter(name: 'range', description: 'Loại khoảng thời gian: day (hôm nay), week (tuần này), month (tháng này), custom (tùy chọn)', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['day', 'week', 'month', 'custom']))]
    #[OA\Parameter(name: 'start_date', description: 'Ngày bắt đầu (bắt buộc khi range=custom). Định dạng: Y-m-d', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'end_date', description: 'Ngày kết thúc (bắt buộc khi range=custom). Định dạng: Y-m-d. Phải >= start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Response(response: 200, description: 'Thành công - Trả về thông tin tổng hợp chi tiêu')]
    #[OA\Response(response: 401, description: 'Unauthorized - Token không hợp lệ hoặc đã hết hạn')]
    #[OA\Response(response: 422, description: 'Validation Error - Dữ liệu không hợp lệ')]
    public function viewSummary(ViewSpendingSummaryRequest $request): JsonResponse
    {
        $result = $this->spendingService->getSummary(
            ViewSpendingSummaryDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
