<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Finance\DTO\RewardHistoryDTO;
use App\Modules\Finance\Http\Requests\ViewRewardHistoryRequest;
use App\Modules\Finance\Interfaces\RewardServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class RewardController extends BaseController
{
    public function __construct(
        private readonly RewardServiceInterface $rewardService,
    ) {}

    #[OA\Get(
        path: '/api/v1/finance/rewards/overview',
        summary: 'Lấy tổng quan điểm thưởng (UC-24)',
        security: [['sanctum' => []]],
        tags: ['Finance - Rewards']
    )]
    #[OA\Response(response: 200, description: 'Thành công')]
    public function overview(Request $request): JsonResponse
    {
        $result = $this->rewardService->getRewardOverview((string) $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy thông tin ví điểm thành công.');
    }

    #[OA\Get(
        path: '/api/v1/finance/rewards/history',
        summary: 'Lấy lịch sử giao dịch điểm (UC-24-4)',
        security: [['sanctum' => []]],
        tags: ['Finance - Rewards']
    )]
    #[OA\Parameter(
        name: 'type', 
        description: 'Loại giao dịch điểm. 1: Tích điểm (Earn), 2: Sử dụng điểm (Redeem), 3: Điểm hết hạn (Expire)', 
        in: 'query', 
        required: false, 
        schema: new OA\Schema(type: 'integer', enum: [1, 2, 3])
    )]
    #[OA\Parameter(name: 'start_date', description: 'Ngày bắt đầu', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'end_date', description: 'Ngày kết thúc', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'per_page', description: 'Số lượng giao dịch mỗi trang', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200, 
        description: 'Thành công',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/RewardTransactionResponse')
        )
    )]
    public function history(ViewRewardHistoryRequest $request): JsonResponse
    {
        $result = $this->rewardService->getHistory(
            RewardHistoryDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy lịch sử giao dịch điểm thành công.');
    }

    #[OA\Get(
        path: '/api/v1/finance/rewards/history/{transactionId}',
        summary: 'Lấy chi tiết giao dịch điểm (UC-24-5)',
        security: [['sanctum' => []]],
        tags: ['Finance - Rewards']
    )]
    #[OA\Parameter(name: 'transactionId', description: 'ID giao dịch', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Thành công')]
    public function showDetail(string $transactionId, Request $request): JsonResponse
    {
        $result = $this->rewardService->getTransactionDetail((string) $request->user()->id, $transactionId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy chi tiết giao dịch điểm thành công.');
    }
}
