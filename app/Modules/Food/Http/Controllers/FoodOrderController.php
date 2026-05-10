<?php

declare(strict_types=1);

namespace App\Modules\Food\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Food\DTO\CreateFoodOrderDTO;
use App\Modules\Food\Http\Requests\CreateFoodOrderRequest;
use App\Modules\Food\Interfaces\FoodOrderServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class FoodOrderController extends BaseController
{
    public function __construct(
        private readonly FoodOrderServiceInterface $foodOrderService
    ) {}

    #[OA\Post(
        path: '/api/v1/food/order',
        summary: 'Đặt món ăn (UC-18)',
        security: [['sanctum' => []]],
        tags: ['Food'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['merchant_id', 'delivery_address', 'delivery_lat', 'delivery_lng', 'customer_phone', 'items'],
                properties: [
                    new OA\Property(property: 'merchant_id', type: 'integer', example: 1),
                    new OA\Property(property: 'delivery_address', type: 'string', example: '123 Nguyễn Trãi, Q1, HCM'),
                    new OA\Property(property: 'delivery_lat', type: 'number', format: 'float', example: 10.762622),
                    new OA\Property(property: 'delivery_lng', type: 'number', format: 'float', example: 106.660172),
                    new OA\Property(property: 'customer_phone', type: 'string', example: '0901234567'),
                    new OA\Property(property: 'notes', type: 'string', example: 'Giao lầu 3'),
                    new OA\Property(property: 'voucher_code', type: 'string', example: 'KM50'),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'menu_item_id', type: 'integer', example: 10),
                                new OA\Property(property: 'quantity', type: 'integer', example: 2),
                                new OA\Property(property: 'notes', type: 'string', example: 'Ít cay'),
                                new OA\Property(
                                    property: 'options',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'name', type: 'string', example: 'Size'),
                                            new OA\Property(property: 'value', type: 'string', example: 'L'),
                                            new OA\Property(property: 'price', type: 'number', example: 5000),
                                        ]
                                    )
                                )
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Đặt món thành công'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ'),
            new OA\Response(response: 422, description: 'Lỗi validation'),
        ]
    )]
    public function create(CreateFoodOrderRequest $request): JsonResponse
    {
        $result = $this->foodOrderService->createOrder(CreateFoodOrderDTO::fromRequest($request));
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/food/order/estimate',
        summary: 'Xem giá ước tính đơn hàng (UC-18)',
        security: [['sanctum' => []]],
        tags: ['Food'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateFoodOrderRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function estimate(CreateFoodOrderRequest $request): JsonResponse
    {
        $result = $this->foodOrderService->calculateEstimate(CreateFoodOrderDTO::fromRequest($request));
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
