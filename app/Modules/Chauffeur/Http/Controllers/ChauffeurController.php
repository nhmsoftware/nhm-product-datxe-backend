<?php

declare(strict_types=1);

namespace App\Modules\Chauffeur\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Chauffeur\DTO\BookChauffeurDTO;
use App\Modules\Chauffeur\Http\Requests\BookChauffeurRequest;
use App\Modules\Chauffeur\Interfaces\ChauffeurServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Controller xử lý các yêu cầu liên quan đến dịch vụ Lái hộ.
 */
final class ChauffeurController extends BaseController
{
    public function __construct(
        private readonly ChauffeurServiceInterface $chauffeurService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/chauffeur/book',
        description: 'Khách hàng đặt dịch vụ lái hộ (tài xế lái xe của khách). Yêu cầu nhập đầy đủ thông tin xe cá nhân.',
        summary: 'Đặt dịch vụ Lái hộ (UC-124)',
        security: [['sanctum' => []]],
        tags: ['Chauffeur']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: [
                'pickup_address', 'pickup_lat', 'pickup_lng',
                'destination_address', 'destination_lat', 'destination_lng',
                'license_plate', 'car_type', 'car_brand', 'car_color'
            ],
            properties: [
                new OA\Property(property: 'pickup_address', type: 'string', example: 'Số 1 Đào Duy Anh, Hà Nội'),
                new OA\Property(property: 'pickup_lat', type: 'number', format: 'float', example: 21.0072),
                new OA\Property(property: 'pickup_lng', type: 'number', format: 'float', example: 105.8428),
                new OA\Property(property: 'destination_address', type: 'string', example: 'Sân bay Nội Bài'),
                new OA\Property(property: 'destination_lat', type: 'number', format: 'float', example: 21.2129),
                new OA\Property(property: 'destination_lng', type: 'number', format: 'float', example: 105.8042),
                new OA\Property(property: 'license_plate', type: 'string', example: '30A-123.45', description: 'Biển số xe của khách'),
                new OA\Property(property: 'car_type', type: 'string', example: 'Sedan 4 chỗ', description: 'Loại xe (SUV, Sedan...)'),
                new OA\Property(property: 'car_brand', type: 'string', example: 'Toyota', description: 'Hãng xe (Toyota, Honda...)'),
                new OA\Property(property: 'car_color', type: 'string', example: 'Trắng', description: 'Màu xe'),
                new OA\Property(property: 'pickup_time', type: 'string', example: '2024-05-20 08:30:00', description: 'Thời gian đón (Y-m-d H:i:s). Bỏ trống nếu đi ngay.'),
                new OA\Property(property: 'voucher_code', type: 'string', example: 'LAIHO10', description: 'Mã giảm giá'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Yêu cầu lái hộ được tạo thành công, hệ thống đang tìm tài xế.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ride_id', type: 'string', example: '123456789'),
                new OA\Property(property: 'total_price', type: 'number', example: 150000),
                new OA\Property(property: 'status_label', type: 'string', example: 'Đang tìm tài xế lái hộ...'),
            ]
        )
    )]
    #[OA\Response(response: 403, description: 'Chưa xác thực số điện thoại')]
    #[OA\Response(response: 422, description: 'Dữ liệu không hợp lệ')]
    public function book(BookChauffeurRequest $request): JsonResponse
    {
        $result = $this->chauffeurService->bookChauffeur(
            BookChauffeurDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Đặt dịch vụ lái hộ thành công.');
    }
}
