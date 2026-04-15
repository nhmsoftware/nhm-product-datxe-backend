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
                    required: ['full_name', 'phone', 'citizen_id', 'vehicle_type', 'vehicle_name', 'vehicle_color', 'vehicle_number', 'vehicle_year', 'cccd_front', 'cccd_back', 'driver_license', 'vehicle_reg', 'criminal_record', 'health_cert', 'portrait', 'insurance'],
                    properties: [
                        new OA\Property(property: 'full_name',      type: 'string',  example: 'Nguyễn Văn A'),
                        new OA\Property(property: 'phone',          type: 'string',  example: '0901234567'),
                        new OA\Property(property: 'citizen_id',     type: 'string',  example: '001234567890'),
                        new OA\Property(property: 'vehicle_type',   type: 'integer', example: 1),
                        new OA\Property(property: 'vehicle_name',   type: 'string',  example: 'Honda Wave Alpha'),
                        new OA\Property(property: 'vehicle_color',  type: 'integer', example: 8),
                        new OA\Property(property: 'vehicle_number', type: 'string',  example: '51K-12345'),
                        new OA\Property(property: 'vehicle_year',   type: 'integer', example: 2020),
                        new OA\Property(property: 'cccd_front',      type: 'string', format: 'binary'),
                        new OA\Property(property: 'cccd_back',       type: 'string', format: 'binary'),
                        new OA\Property(property: 'driver_license',  type: 'string', format: 'binary'),
                        new OA\Property(property: 'vehicle_reg',     type: 'string', format: 'binary'),
                        new OA\Property(property: 'criminal_record', type: 'string', format: 'binary'),
                        new OA\Property(property: 'health_cert',     type: 'string', format: 'binary'),
                        new OA\Property(property: 'portrait',        type: 'string', format: 'binary'),
                        new OA\Property(property: 'insurance',       type: 'string', format: 'binary'),
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
