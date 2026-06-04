<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Pricing\DTO\UpdateScheduledPricingDTO;
use App\Modules\Pricing\Http\Requests\AdminScheduledPricingRequest;
use App\Modules\Pricing\Interfaces\ScheduledPricingServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class AdminScheduledPricingController extends BaseController
{
    public function __construct(
        private readonly ScheduledPricingServiceInterface $service
    ) {}

    /**
     * UC-121: Lấy cấu hình giá đặt trước và chế độ phân phối hiện tại
     */
    #[OA\Get(
        path: '/api/v1/admin/pricing/scheduled',
        summary: 'Lấy cấu hình phân phối chuyến đặt trước (UC-121)',
        security: [['sanctum' => []]],
        tags: ['Admin Scheduled Pricing'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'pricing', 
                            properties: [
                                new OA\Property(property: 'surcharges', type: 'object'),
                                new OA\Property(property: 'rules', type: 'array', items: new OA\Items(type: 'object'))
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'dispatch_mode', type: 'integer', example: 1, description: '1 = Admin phân phối, 2 = Tự động'),
                        new OA\Property(property: 'dispatch_mode_label', type: 'string', example: 'Admin phân phối (Thủ công)'),
                        new OA\Property(property: 'is_admin_controlled', type: 'boolean', example: true),
                        new OA\Property(property: 'auto_push_internal', type: 'boolean', example: false),
                    ]
                )
            )
        ]
    )]
    public function show(): JsonResponse
    {
        $result = $this->service->getCurrentSettings();
        return $this->sendSuccess($result->getData(), 'Lấy cấu hình giá đặt trước thành công.');
    }

    /**
     * UC-121: Cập nhật cấu hình giá đặt trước
     */
    #[OA\Post(
        path: '/api/v1/admin/pricing/scheduled',
        summary: 'Cập nhật cấu hình giá đặt trước (UC-121)',
        security: [['sanctum' => []]],
        tags: ['Admin Scheduled Pricing'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'pre_book_surcharge', type: 'number', format: 'float', example: 20000),
                    new OA\Property(property: 'night_surcharge', type: 'number', format: 'float', example: 15000),
                    new OA\Property(property: 'holiday_surcharge', type: 'number', format: 'float', example: 50000),
                    new OA\Property(property: 'waiting_surcharge', type: 'number', format: 'float', example: 5000),
                    new OA\Property(property: 'toll_surcharge', type: 'number', format: 'float', example: 10000),
                    new OA\Property(property: 'dispatch_mode', type: 'integer', example: 1),
                    new OA\Property(
                        property: 'rules', 
                        type: 'array', 
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'service_type', type: 'integer', example: 6),
                                new OA\Property(property: 'ride_mode', type: 'string', example: 'shared'),
                                new OA\Property(property: 'vehicle_type', type: 'integer', example: 2),
                                new OA\Property(property: 'airport_id', type: 'string', nullable: true),
                                new OA\Property(
                                    property: 'ranges',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'start_km', type: 'number', format: 'float', example: 0),
                                            new OA\Property(property: 'end_km', type: 'number', format: 'float', example: 10),
                                            new OA\Property(property: 'price', type: 'number', format: 'float', example: 150000),
                                            new OA\Property(property: 'unit', type: 'string', example: 'per_passenger'),
                                        ]
                                    )
                                )
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Thành công')]
    )]
    public function update(AdminScheduledPricingRequest $request): JsonResponse
    {
        $dto = UpdateScheduledPricingDTO::fromRequest($request);
        $result = $this->service->updateSettings($dto);

        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Cập nhật cấu hình giá thành công.');
    }

    /**
     * UC-122: Bật/Tắt chế độ phân phối thủ công (Admin) cho chuyến đặt trước.
     *
     * BẬT (mode=1) → Admin kiểm soát, tài xế KHÔNG thấy chuyến.
     * TẮT (mode=2) → Tự động, tài xế thấy và nhận chuyến.
     */
    #[OA\Post(
        path: '/api/v1/admin/pricing/scheduled/toggle-dispatch',
        summary: 'Bật/Tắt chế độ phân phối chuyến đặt trước (UC-122)',
        security: [['sanctum' => []]],
        tags: ['Admin Scheduled Pricing'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['mode'],
                properties: [
                    new OA\Property(
                        property: 'mode',
                        type: 'integer',
                        example: 2,
                        description: '1 = Bật Admin phân phối thủ công (tài xế không thấy chuyến). 2 = Tắt Admin / Tự động đẩy cho tài xế.'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'dispatch_mode', type: 'integer', example: 2),
                        new OA\Property(property: 'dispatch_mode_label', type: 'string', example: 'Tự động (Tài xế nhận chuyến)'),
                        new OA\Property(property: 'is_admin_controlled', type: 'boolean', example: false),
                        new OA\Property(property: 'affected_rides', type: 'integer', example: 12),
                        new OA\Property(property: 'message', type: 'string', example: 'Đã bật chế độ tự động. 12 chuyến đặt trước đã được đẩy cho tài xế.'),
                    ]
                )
            )
        ]
    )]
    public function toggleDispatch(Request $request): JsonResponse
    {
        $mode = (int) $request->input('mode');

        if (!in_array($mode, [1, 2])) {
            return $this->sendError('Mode không hợp lệ. Chỉ chấp nhận 1 (Admin) hoặc 2 (Tự động).', 422);
        }

        $result = $this->service->toggleDispatchMode($mode);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getData()['message'] ?? 'Cập nhật thành công.');
    }

    /**
     * UC-122: Bật/Tắt chế độ tự động đẩy chuyến cho đội xe nhà
     */
    #[OA\Post(
        path: '/api/v1/admin/pricing/scheduled/toggle-internal-auto-push',
        summary: 'Bật/Tắt tự động đẩy đơn cho xe nhà (UC-122)',
        security: [['sanctum' => []]],
        tags: ['Admin Scheduled Pricing'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['is_auto_push'],
                properties: [
                    new OA\Property(
                        property: 'is_auto_push',
                        type: 'boolean',
                        example: true,
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công'
            )
        ]
    )]
    public function toggleInternalAutoPush(Request $request): JsonResponse
    {
        $isAutoPush = filter_var($request->input('is_auto_push'), FILTER_VALIDATE_BOOLEAN);

        $result = $this->service->toggleInternalAutoPush($isAutoPush);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getData()['message'] ?? 'Cập nhật thành công.');
    }
}
