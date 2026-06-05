<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Driver\DTO\RegisterDriverSubmitDTO;
use App\Modules\Driver\Http\Requests\RegisterDriverSubmitRequest;
use App\Modules\Driver\Interfaces\DriverRegistrationServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * @OA\Tag(name="Driver", description="Đăng ký & quản lý tài xế")
 */
final class DriverController extends BaseController
{
    public function __construct(
        private readonly DriverRegistrationServiceInterface $driverRegistrationService,
    ) {}

    /**
     * UC-30 Lấy danh sách dịch vụ tài xế có thể đăng ký.
     *
     * API này KHÔNG yêu cầu đăng nhập. Frontend gọi trước khi hiển thị form đăng ký
     * để lấy danh sách dịch vụ và các loại xe tương ứng.
     *
     * Luồng sử dụng điển hình:
     *  1. Frontend gọi API này để lấy danh sách dịch vụ.
     *  2. User chọn dịch vụ muốn đăng ký → frontend lọc `supported_vehicle_types`
     *     để chỉ hiển thị các loại xe hợp lệ cho dịch vụ đó.
     *  3. User điền thông tin và nộp hồ sơ qua POST /api/v1/driver/register/submit.
     */
    #[OA\Get(
        path: '/api/v1/driver/register/services',
        summary: 'UC-30: Lấy danh sách dịch vụ tài xế có thể đăng ký',
        description: 'Trả về toàn bộ các dịch vụ vận chuyển mà tài xế có thể đăng ký hoạt động, kèm danh sách loại xe hỗ trợ cho từng dịch vụ. API này là **public** — không cần xác thực. Frontend dùng để render form đăng ký tài xế.',
        tags: ['Driver'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Danh sách dịch vụ',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: ''),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            description: 'Danh sách dịch vụ tài xế có thể đăng ký',
                            items: new OA\Items(
                                required: ['id', 'label', 'supported_vehicle_types'],
                                properties: [
                                    new OA\Property(
                                        property: 'id',
                                        type: 'integer',
                                        description: 'ID dịch vụ — dùng để submit vào trường `services[]` khi đăng ký',
                                        example: 1,
                                        enum: [1, 2, 3, 4, 5, 6, 7, 8],
                                    ),
                                    new OA\Property(
                                        property: 'label',
                                        type: 'string',
                                        description: 'Tên hiển thị của dịch vụ',
                                        example: 'Xe ôm',
                                    ),
                                    new OA\Property(
                                        property: 'supported_vehicle_types',
                                        type: 'array',
                                        description: 'Danh sách các loại phương tiện hợp lệ cho dịch vụ này. Frontend dùng để validate trường `vehicle_type` khi user chọn dịch vụ.',
                                        items: new OA\Items(
                                            required: ['id', 'label'],
                                            properties: [
                                                new OA\Property(
                                                    property: 'id',
                                                    type: 'integer',
                                                    description: 'ID loại xe — dùng để submit vào trường `vehicle_type` khi đăng ký',
                                                    example: 1,
                                                    enum: [1, 2, 3, 4, 5],
                                                ),
                                                new OA\Property(
                                                    property: 'label',
                                                    type: 'string',
                                                    description: 'Tên hiển thị của loại xe',
                                                    example: 'Xe máy',
                                                ),
                                            ]
                                        ),
                                    ),
                                ]
                            ),
                            example: [
                                [
                                    'id'    => 1,
                                    'label' => 'Xe ôm',
                                    'supported_vehicle_types' => [
                                        ['id' => 1, 'label' => 'Xe máy'],
                                    ],
                                ],
                                [
                                    'id'    => 2,
                                    'label' => 'Taxi 4 chỗ',
                                    'supported_vehicle_types' => [
                                        ['id' => 2, 'label' => 'Ô tô 4 chỗ'],
                                    ],
                                ],
                                [
                                    'id'    => 5,
                                    'label' => 'Giao hàng',
                                    'supported_vehicle_types' => [
                                        ['id' => 1, 'label' => 'Xe máy'],
                                        ['id' => 2, 'label' => 'Ô tô 4 chỗ'],
                                        ['id' => 3, 'label' => 'Ô tô 7 chỗ'],
                                        ['id' => 4, 'label' => 'Ô tô 9 chỗ'],
                                    ],
                                ],
                            ],
                        ),
                    ]
                )
            ),
        ]
    )]
    public function getRegistrationServices(): JsonResponse
    {
        $result = $this->driverRegistrationService->getRegistrationServices();
        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    /**
     * UC-30 Nộp hồ sơ đăng ký tài xế (Thông tin cá nhân, phương tiện, KYC).
     */
    #[OA\Post(
        path: '/api/v1/driver/register/submit',
        summary: 'UC-30: Nộp hồ sơ đăng ký tài xế (Thông tin + KYC)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['full_name', 'phone', 'citizen_id', 'vehicle_type', 'vehicle_name', 'vehicle_color', 'vehicle_number', 'vehicle_year', 'services', 'cccd_front', 'cccd_back', 'driver_license', 'vehicle_reg', 'criminal_record', 'health_cert', 'portrait', 'insurance'],
                    properties: [
                        new OA\Property(property: 'full_name', description: 'Họ và tên', type: 'string', example: 'Nguyễn Văn A'),
                        new OA\Property(property: 'phone', description: 'Số điện thoại', type: 'string', example: '0901234567'),
                        new OA\Property(property: 'citizen_id', description: 'Số CMND', type: 'string', example: '001234567890'),
                        new OA\Property(
                            property: 'vehicle_type', 
                            description: 'Loại phương tiện. 1: Xe Máy (Bike), 2: Ô Tô 4 Chỗ (Car 4 Seats), 3: Ô Tô 7 Chỗ (Car 7 Seats), 4: Ô Tô 9 Chỗ (Car 9 Seats)', 
                            type: 'integer', 
                            example: 1
                        ),
                        new OA\Property(property: 'vehicle_name', description: 'Tên phương tiện', type: 'string', example: 'Honda Wave Alpha'),
                        new OA\Property(
                            property: 'vehicle_color', 
                            description: 'Màu sắc xe tiêu chuẩn. 0: Màu Khác, 1: Đỏ, 2: Xanh lá, 3: Xanh dương, 4: Vàng, 5: Cam, 6: Tím, 7: Nâu, 8: Đen, 9: Trắng', 
                            type: 'integer', 
                            example: 8
                        ),
                        new OA\Property(property: 'vehicle_number', description: 'Biển số xe', type: 'string', example: '51K-12345'),
                        new OA\Property(property: 'vehicle_year', description: 'Năm xuất xứ xe', type: 'integer', example: 2020),
                        new OA\Property(
                            property: 'services', 
                            description: 'Danh sách ID dịch vụ đăng ký (mảng số nguyên)', 
                            type: 'array', 
                            items: new OA\Items(type: 'integer', example: 1)
                        ),
                        new OA\Property(property: 'cccd_front', description: 'CCCD trước', type: 'string', format: 'binary'),
                        new OA\Property(property: 'cccd_back', description: 'CCCD sau', type: 'string', format: 'binary'),
                        new OA\Property(property: 'driver_license', description: 'Bằng lái xe', type: 'string', format: 'binary'),
                        new OA\Property(property: 'vehicle_reg', description: 'đăng ký xe', type: 'string', format: 'binary'),
                        new OA\Property(property: 'criminal_record', description: 'Lý lịch tư pháp', type: 'string', format: 'binary'),
                        new OA\Property(property: 'health_cert', description: 'Giấy chứng nhận sức khỏe', type: 'string', format: 'binary'),
                        new OA\Property(property: 'portrait', description: 'Hình ảnh', type: 'string', format: 'binary'),
                        new OA\Property(property: 'insurance', description: 'Bảo hiểm xe', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        tags: ['Driver'],
        responses: [
            new OA\Response(response: 200, description: 'Hồ sơ đăng ký tạo thành công — Pending Approval'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ'),
            new OA\Response(response: 403, description: 'Tài khoản không được quyền đăng ký'),
            new OA\Response(response: 409, description: 'Đã là tài xế / hồ sơ đang pending'),
            new OA\Response(response: 422, description: 'File không hợp lệ / CCCD trùng / Biển số trùng'),
        ]
    )]
    public function submit(RegisterDriverSubmitRequest $request): JsonResponse
    {
        $result = $this->driverRegistrationService->submitRegistration(
            RegisterDriverSubmitDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
