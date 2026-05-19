<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Merchant\DTO\RegisterMerchantDTO;
use App\Modules\Merchant\Http\Requests\RegisterMerchantRequest;
use App\Modules\Merchant\Interfaces\MerchantRegistrationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class MerchantRegistrationController extends BaseController
{
    public function __construct(
        private readonly MerchantRegistrationServiceInterface $registrationService
    ) {}

    #[OA\Post(
        path: '/api/v1/merchant/register',
        summary: 'UC-52: Đăng ký Merchant (Thông tin + KYC)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['full_name', 'phone', 'citizen_id', 'store_name', 'store_address', 'latitude', 'longitude', 'business_type', 'citizen_id_image', 'store_image'],
                    properties: [
                        new OA\Property(property: 'full_name', description: 'Họ và tên', type: 'string', example: 'Nguyễn Văn A'),
                        new OA\Property(property: 'phone', description: 'Số điện thoại', type: 'string', example: '0901234567'),
                        new OA\Property(property: 'citizen_id', description: 'Số CMND/CCCD', type: 'string', example: '001234567890'),
                        new OA\Property(property: 'store_name', description: 'Tên cửa hàng', type: 'string', example: 'Phở Gia Truyền'),
                        new OA\Property(property: 'store_address', description: 'Địa chỉ cửa hàng', type: 'string', example: '123 Đường ABC, Quận 1'),
                        new OA\Property(property: 'latitude', description: 'Vĩ độ của cửa hàng', type: 'number', format: 'float', example: 21.07207),
                        new OA\Property(property: 'longitude', description: 'Kinh độ của cửa hàng', type: 'number', format: 'float', example: 105.7739283),
                        new OA\Property(property: 'business_type', description: 'Loại hình kinh doanh', type: 'string', example: 'Ăn uống'),
                        new OA\Property(property: 'citizen_id_image', description: 'Ảnh CCCD', type: 'string', format: 'binary'),
                        new OA\Property(property: 'business_license_image', description: 'Giấy phép kinh doanh', type: 'string', format: 'binary'),
                        new OA\Property(property: 'store_image', description: 'Ảnh cửa hàng', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        tags: ['Merchant']
    )]
    #[OA\Response(response: 200, description: 'Đăng ký thành công — Chờ xét duyệt')]
    public function register(RegisterMerchantRequest $request): JsonResponse
    {
        $result = $this->registrationService->submitRegistration(RegisterMerchantDTO::fromRequest($request));
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
