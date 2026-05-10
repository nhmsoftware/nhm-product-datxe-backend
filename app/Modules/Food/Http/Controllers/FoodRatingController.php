<?php

declare(strict_types=1);

namespace App\Modules\Food\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Food\DTO\RateFoodDTO;
use App\Modules\Food\Http\Requests\RateFoodRequest;
use App\Modules\Food\Interfaces\FoodRatingServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class FoodRatingController extends BaseController
{
    public function __construct(
        private readonly FoodRatingServiceInterface $foodRatingService
    ) {}

    #[OA\Post(
        path: '/api/v1/food/order/{orderId}/rate',
        summary: 'Đánh giá đơn đồ ăn (UC-20)',
        security: [['sanctum' => []]],
        tags: ['Food'],
        parameters: [
            new OA\Parameter(
                name: 'orderId',
                in: 'path',
                description: 'ID của đơn hàng đồ ăn (ULID)',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RateFoodRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Đánh giá thành công'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ (đã đánh giá hoặc chưa hoàn thành)'),
            new OA\Response(response: 403, description: 'Không có quyền đánh giá'),
            new OA\Response(response: 404, description: 'Không tìm thấy đơn hàng'),
        ]
    )]
    public function rate(RateFoodRequest $request): JsonResponse
    {
        $result = $this->foodRatingService->rateOrder(RateFoodDTO::fromRequest($request));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
