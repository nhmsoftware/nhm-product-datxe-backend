<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use Illuminate\Http\JsonResponse;
use App\Modules\Ride\Http\Requests\CreateDraftRideRequest;
use App\Modules\Ride\DTO\CreateDraftRideDTO;
use OpenApi\Attributes as OA;

class RideController extends BaseController
{
    public function __construct(
        protected RideServiceInterface $rideService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/ride/draft',
        description: 'Tạo một chuyến xe tạm thời (draft) sau khi người dùng nhập địa điểm và chọn loại xe. Hỗ trợ kiểm tra xác thực số điện thoại (A13 flow).',
        summary: 'Tạo chuyến xe tạm thời (UC-08)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['pickup_address', 'pickup_lat', 'pickup_lng', 'destination_address', 'destination_lat', 'destination_lng', 'vehicle_type'],
            properties: [
                new OA\Property(property: 'pickup_address', type: 'string', example: 'Số 1 Đào Duy Anh, Đống Đa, Hà Nội'),
                new OA\Property(property: 'pickup_lat', type: 'number', format: 'float', example: 21.0072),
                new OA\Property(property: 'pickup_lng', type: 'number', format: 'float', example: 105.8428),
                new OA\Property(property: 'destination_address', type: 'string', example: 'Vincom Mega Mall Ocean Park, Gia Lâm, Hà Nội'),
                new OA\Property(property: 'destination_lat', type: 'number', format: 'float', example: 20.9944),
                new OA\Property(property: 'destination_lng', type: 'number', format: 'float', example: 105.9458),
                new OA\Property(property: 'vehicle_type', description: '1: Bike, 2: Car 4 seats, 3: Car 7 seats, 4: Car 9 seats', type: 'integer', example: 1),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Chuyến xe tạm thời được tạo thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Vị trí đã được ghi nhận. Vui lòng chọn loại xe.'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Người dùng chưa xác thực số điện thoại (A13)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Vui lòng xác thực số điện thoại để tiếp tục.'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'error_code', type: 'string', example: 'PHONE_NOT_VERIFIED')
                ], type: 'object')
            ]
        )
    )]
    public function createDraft(CreateDraftRideRequest $request): JsonResponse
    {
        // Nhận dữ liệu đã qua validation và khởi tạo DTO
        $dto = CreateDraftRideDTO::fromArray($request->validated());

        // Gửi DTO xuống lớp Service xử lý thay vì array thô
        $result = $this->rideService->createDraft($dto);

        if ($result->isError()) {
            return $this->sendError(
                $result->getMessage(),
                $result->getCode(),
            );
        }

        return $this->sendSuccess(
            data: $result->getData(),
            message: $result->getMessage(),
            code: $result->getCode(),
        );

    }
}
