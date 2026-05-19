<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Merchant\DTO\GetNearbyMerchantsDTO;
use App\Modules\Merchant\Http\Requests\GetNearbyMerchantsRequest;
use App\Modules\Merchant\Interfaces\CustomerMerchantServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class CustomerMerchantController extends BaseController
{
    public function __construct(
        private readonly CustomerMerchantServiceInterface $customerMerchantService
    ) {}

    #[OA\Get(
        path: '/api/v1/customer/merchants',
        summary: 'Lấy danh sách các cửa hàng/nhà hàng lân cận dựa vào vị trí khách hàng',
        security: [['sanctum' => []]],
        tags: ['Customer Merchant'],
        parameters: [
            new OA\Parameter(
                name: 'latitude',
                in: 'query',
                description: 'Vĩ độ của khách hàng',
                required: true,
                schema: new OA\Schema(type: 'number', format: 'float', example: 10.762622)
            ),
            new OA\Parameter(
                name: 'longitude',
                in: 'query',
                description: 'Kinh độ của khách hàng',
                required: true,
                schema: new OA\Schema(type: 'number', format: 'float', example: 106.660172)
            ),
            new OA\Parameter(
                name: 'radius_in_km',
                in: 'query',
                description: 'Bán kính tìm kiếm (km), mặc định là 10km',
                required: false,
                schema: new OA\Schema(type: 'number', format: 'float', example: 10.0)
            ),
            new OA\Parameter(
                name: 'keyword',
                in: 'query',
                description: 'Tìm kiếm cửa hàng theo tên',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Cơm Tấm')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Số thứ tự trang',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Số lượng phần tử trên trang',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 20)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Thành công'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'items',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', example: '161351382691115916'),
                                            new OA\Property(property: 'store_name', type: 'string', example: 'Cửa hàng Test'),
                                            new OA\Property(property: 'store_address', type: 'string', example: '123 Test Street'),
                                            new OA\Property(property: 'lat', type: 'number', format: 'float', example: 10.762622),
                                            new OA\Property(property: 'lng', type: 'number', format: 'float', example: 106.660172),
                                            new OA\Property(property: 'opening_time', type: 'string', example: '08:00:00'),
                                            new OA\Property(property: 'closing_time', type: 'string', example: '22:00:00'),
                                            new OA\Property(property: 'is_open', type: 'boolean', example: true),
                                            new OA\Property(property: 'store_image', type: 'string', nullable: true),
                                            new OA\Property(property: 'average_rating', type: 'number', format: 'float', example: 4.5),
                                            new OA\Property(property: 'total_orders', type: 'integer', example: 100),
                                            new OA\Property(property: 'distance', type: 'number', format: 'float', example: 1.25),
                                            new OA\Property(property: 'opening_hours', type: 'array', items: new OA\Items())
                                        ]
                                    )
                                ),
                                new OA\Property(
                                    property: 'pagination',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total', type: 'integer', example: 5),
                                        new OA\Property(property: 'count', type: 'integer', example: 5),
                                        new OA\Property(property: 'per_page', type: 'integer', example: 20),
                                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                        new OA\Property(property: 'total_pages', type: 'integer', example: 1)
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 422, description: 'Dữ liệu đầu vào không hợp lệ')
        ]
    )]
    public function index(GetNearbyMerchantsRequest $request): JsonResponse
    {
        $dto = GetNearbyMerchantsDTO::fromRequest($request);
        $result = $this->customerMerchantService->getNearbyMerchants($dto);
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/customer/merchants/{id}',
        summary: 'Xem chi tiết thông tin một cửa hàng',
        security: [['sanctum' => []]],
        tags: ['Customer Merchant'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID của cửa hàng',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Thành công'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: '161351382691115916'),
                                new OA\Property(property: 'store_name', type: 'string', example: 'Cửa hàng Test'),
                                new OA\Property(property: 'store_address', type: 'string', example: '123 Test Street'),
                                new OA\Property(property: 'lat', type: 'number', format: 'float', example: 10.762622),
                                new OA\Property(property: 'lng', type: 'number', format: 'float', example: 106.660172),
                                new OA\Property(property: 'opening_time', type: 'string', example: '08:00:00'),
                                new OA\Property(property: 'closing_time', type: 'string', example: '22:00:00'),
                                new OA\Property(property: 'is_open', type: 'boolean', example: true),
                                new OA\Property(property: 'store_image', type: 'string', nullable: true),
                                new OA\Property(property: 'average_rating', type: 'number', format: 'float', example: 4.5),
                                new OA\Property(property: 'total_orders', type: 'integer', example: 100),
                                new OA\Property(property: 'opening_hours', type: 'array', items: new OA\Items())
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 404, description: 'Không tìm thấy cửa hàng')
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->customerMerchantService->getMerchantDetail($id);
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/customer/merchants/{id}/menu',
        summary: 'Lấy danh sách thực đơn (Menu) chi tiết của cửa hàng',
        security: [['sanctum' => []]],
        tags: ['Customer Merchant'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID của cửa hàng',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Thành công'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', example: '1'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Món chính'),
                                    new OA\Property(property: 'order', type: 'integer', example: 0),
                                    new OA\Property(
                                        property: 'items',
                                        type: 'array',
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: 'id', type: 'string', example: '2'),
                                                new OA\Property(property: 'name', type: 'string', example: 'Cơm tấm sườn'),
                                                new OA\Property(property: 'description', type: 'string', example: 'Cơm sườn ngon tuyệt'),
                                                new OA\Property(property: 'price', type: 'number', format: 'float', example: 35000.0),
                                                new OA\Property(property: 'image_path', type: 'string', nullable: true),
                                                new OA\Property(property: 'is_available', type: 'boolean', example: true),
                                                new OA\Property(property: 'order', type: 'integer', example: 1),
                                                new OA\Property(property: 'rating', type: 'number', format: 'float', example: 5.0),
                                                new OA\Property(
                                                    property: 'sizes',
                                                    type: 'array',
                                                    items: new OA\Items()
                                                ),
                                                new OA\Property(
                                                    property: 'toppings',
                                                    type: 'array',
                                                    items: new OA\Items()
                                                )
                                            ]
                                        )
                                    )
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 404, description: 'Không tìm thấy cửa hàng hoặc cửa hàng chưa hoạt động')
        ]
    )]
    public function menu(string $id): JsonResponse
    {
        $result = $this->customerMerchantService->getMerchantMenu($id);
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
