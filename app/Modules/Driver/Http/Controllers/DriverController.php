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
     * - Không truyền `vehicle_type_id` → trả toàn bộ dịch vụ kèm `supported_vehicle_types`.
     * - Truyền `vehicle_type_id`       → trả chỉ các dịch vụ mà loại xe đó có thể đăng ký.
     *
     * Logic mới:
     *  1. `vehicle_type_id` được đối chiếu với catalog `vehicle_types`.
     *  2. Hệ thống đọc `service_scopes` của loại xe để suy ra dịch vụ phù hợp.
     *  3. Không còn phụ thuộc enum loại xe cố định ở request/response contract.
     */
    #[OA\Get(
        path: '/api/v1/driver/register/services',
        summary: 'UC-30: Lấy danh sách dịch vụ tài xế có thể đăng ký',
description: <<<'DESC'
**Public API — không cần xác thực.**

Có 2 chế độ hoạt động:

- **Không truyền `vehicle_type_id`**: Trả toàn bộ dịch vụ, mỗi dịch vụ kèm `supported_vehicle_types` là danh sách loại xe hỗ trợ.
- **Truyền `vehicle_type_id`**: Lọc và chỉ trả về các dịch vụ mà loại xe đó có thể đăng ký (không có `supported_vehicle_types`).

**Nguồn dữ liệu:**
- `vehicle_type_id` được lấy từ catalog `vehicle_types`
- dịch vụ phù hợp được suy ra từ `service_scopes` của loại xe

**Luồng dùng chuẩn:**
1. Frontend gọi `GET /api/v1/meta/vehicle-types`
2. User chọn loại xe
3. Frontend gọi `GET /api/v1/driver/register/services?vehicle_type_id={id}`
4. Submit hồ sơ qua `POST /api/v1/driver/register/submit`
DESC,
        tags: ['Driver'],
        parameters: [
            new OA\Parameter(
                name: 'vehicle_type_id',
                in: 'query',
                required: false,
                description: 'ID loại xe trong catalog `vehicle_types`. Nếu truyền vào → chỉ trả về dịch vụ phù hợp với loại xe đó. Nếu bỏ trống → trả toàn bộ dịch vụ.',
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
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
                            description: 'Nếu có `vehicle_type_id`: chỉ có `id` + `label`. Nếu không có: kèm thêm `supported_vehicle_types` lấy từ catalog loại xe.',
                            items: new OA\Items(
                                required: ['id', 'label'],
                                properties: [
                                    new OA\Property(
                                        property: 'id',
                                        type: 'integer',
                                        description: 'ID dịch vụ — dùng để submit vào `services[]` khi đăng ký',
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
                                        nullable: true,
                                        description: 'Chỉ có khi KHÔNG truyền `vehicle_type_id`. Danh sách loại xe trong catalog hiện hỗ trợ dịch vụ này.',
                                        items: new OA\Items(
                                            required: ['id', 'label'],
                                            properties: [
                                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                                new OA\Property(property: 'label', type: 'string', example: 'Xe máy'),
                                            ]
                                        ),
                                    ),
                                ],
                            ),
                            example: [
                                ['id' => 1, 'label' => 'Xe ôm'],
                                ['id' => 2, 'label' => 'Taxi 4 chỗ'],
                                ['id' => 5, 'label' => 'Giao hàng'],
                                ['id' => 6, 'label' => 'Xe đi tỉnh'],
                            ],
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'vehicle_type_id không hợp lệ',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Loại xe không hợp lệ.'),
                    ]
                )
            ),
        ]
    )]
    public function getRegistrationServices(\Illuminate\Http\Request $request): JsonResponse
    {
        $vehicleTypeId = $request->query('vehicle_type_id') !== null
            ? (int) $request->query('vehicle_type_id')
            : null;

        $result = $this->driverRegistrationService->getRegistrationServices($vehicleTypeId);
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
                    required: ['full_name', 'phone', 'citizen_id', 'vehicle_type_id', 'vehicle_name', 'vehicle_color', 'vehicle_number', 'vehicle_year', 'services', 'cccd_front', 'cccd_back', 'driver_license', 'vehicle_reg', 'criminal_record', 'health_cert', 'portrait', 'insurance'],
                    properties: [
                        new OA\Property(property: 'full_name', description: 'Họ và tên', type: 'string', example: 'Nguyễn Văn A'),
                        new OA\Property(property: 'phone', description: 'Số điện thoại', type: 'string', example: '0901234567'),
                        new OA\Property(property: 'citizen_id', description: 'Số CCCD', type: 'string', example: '001234567890'),
                        new OA\Property(
                            property: 'vehicle_type_id',
                            description: 'ID loại phương tiện trong catalog `vehicle_types`',
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
                        new OA\Property(property: 'vehicle_year', description: 'Năm sản xuất xe', type: 'integer', example: 2020),
                        new OA\Property(
                            property: 'services', 
                            description: 'Danh sách ID dịch vụ đăng ký. Các ID hợp lệ nên lấy từ API `GET /api/v1/driver/register/services` theo loại xe đã chọn.', 
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
            new OA\Response(response: 409, description: 'Đã là tài xế / hồ sơ đang chờ duyệt'),
            new OA\Response(response: 422, description: 'Loại xe không hợp lệ / dịch vụ không phù hợp / file không hợp lệ / CCCD trùng / biển số trùng'),
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
