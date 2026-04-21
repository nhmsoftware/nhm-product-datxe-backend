<?php

declare(strict_types=1);

namespace App\Modules\Operation\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Operation\DTO\UpdateLocationDTO;
use App\Modules\Operation\DTO\GetNavigationDTO;
use App\Modules\Operation\Http\Requests\UpdateLocationRequest;
use App\Modules\Operation\Interfaces\OperationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Controller điều phối các hoạt động định vị và dẫn đường.
 */
final class OperationController extends BaseController
{
    public function __construct(
        private readonly OperationServiceInterface $operationService,
    ) {
    }

    #[OA\Post(
        path: '/api/v1/operation/location',
        description: 'API để User (Tài xế/Khách hàng) cập nhật tọa độ GPS. Hệ thống sử dụng Redis để lưu trữ real-time và đồng bộ DB có tiết lưu (60s/lần) để đảm bảo hiệu năng cao cho 10.000+ user.',
        summary: 'UC-35: Cập nhật vị trí hiện tại (Driver/Customer)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['lat', 'lng'],
                properties: [
                    new OA\Property(property: 'lat', description: 'Vĩ độ', type: 'number', format: 'float', example: 10.762622),
                    new OA\Property(property: 'lng', description: 'Kinh độ', type: 'number', format: 'float', example: 106.660172),
                ]
            )
        ),
        tags: ['Operation'],
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 422, description: 'Dữ liệu tọa độ không hợp lệ'),
        ]
    )]
    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        $result = $this->operationService->updateLocation(
            UpdateLocationDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Cập nhật vị trí thành công.');
    }

    #[OA\Get(
        path: '/api/v1/operation/navigation/{rideId}',
        summary: 'UC-34: Lấy thông tin dẫn đường (Polyline/Distance/Duration)',
        description: 'Lấy dữ liệu dẫn đường thông minh: Tài xế sẽ thấy đường đến Pickup (nếu vừa nhận) hoặc Destination (nếu đang chở khách). Khách hàng sẽ thấy đường từ Tài xế đến mình để theo dõi.',
        security: [['sanctum' => []]],
        tags: ['Operation'],
        parameters: [
            new OA\Parameter(name: 'rideId', in: 'path', description: 'ID chuyến xe', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lấy dữ liệu thành công'),
            new OA\Response(response: 403, description: 'Không có quyền xem chuyến xe này'),
            new OA\Response(response: 404, description: 'Không tìm thấy chuyến xe'),
        ]
    )]
    public function getNavigation(string $rideId, Request $request): JsonResponse
    {
        $result = $this->operationService->getNavigation(
            GetNavigationDTO::fromRequest($rideId, $request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy dữ liệu dẫn đường thành công.');
    }
}
