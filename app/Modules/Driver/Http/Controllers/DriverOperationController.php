<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Driver\DTO\AcceptOrderDTO;
use App\Modules\Driver\DTO\CancelOrderDTO;
use App\Modules\Driver\DTO\RejectOrderDTO;
use App\Modules\Driver\DTO\ToggleOnlineStatusDTO;
use App\Modules\Driver\Http\Requests\AcceptOrderRequest;
use App\Modules\Driver\Http\Requests\CancelOrderRequest;
use App\Modules\Driver\Http\Requests\RejectOrderRequest;
use App\Modules\Driver\Http\Requests\ToggleOnlineStatusRequest;
use App\Modules\Driver\Interfaces\DriverOperationServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class DriverOperationController extends BaseController
{
    public function __construct(
        private readonly DriverOperationServiceInterface $driverOperationService,
    ) {}

    #[OA\Put(
        path: '/api/v1/driver/status',
        summary: 'UC-31: Bật/Tắt trạng thái hoạt động (Go Online/Offline)',
        description: 'Tài xế có thể bật/tắt trạng thái bất cứ lúc nào. Nếu tắt Offline khi đang có chuyến, tài xế vẫn hoàn thành chuyến cũ nhưng sẽ không được gán thêm chuyến mới.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['is_online'],
                properties: [
                    new OA\Property(property: 'is_online', description: 'True để Online, False để Offline', type: 'boolean', example: true),
                    new OA\Property(property: 'current_lat', description: 'Bắt buộc nếu is_online = true', type: 'number', format: 'float', example: 10.776889),
                    new OA\Property(property: 'current_lng', description: 'Bắt buộc nếu is_online = true', type: 'number', format: 'float', example: 106.700806),
                ]
            )
        ),
        tags: ['Driver'],
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật trạng thái thành công'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ'),
            new OA\Response(response: 403, description: 'Tài khoản chưa được duyệt, bị khóa hoặc đang chờ (Cooldown)'),
            new OA\Response(response: 422, description: 'Lỗi xử lý nghiệp vụ'),
        ]
    )]
    public function toggleStatus(ToggleOnlineStatusRequest $request): JsonResponse
    {
        $result = $this->driverOperationService->toggleOnlineStatus(
            ToggleOnlineStatusDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/driver/ride/{rideId}/accept',
        summary: 'UC-32: Chấp nhận đơn hàng/chuyến xe',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_lat', 'current_lng'],
                properties: [
                    new OA\Property(property: 'current_lat', type: 'number', format: 'float', example: 10.776889),
                    new OA\Property(property: 'current_lng', type: 'number', format: 'float', example: 106.700806),
                ]
            )
        ),
        tags: ['Driver'],
        parameters: [
            new OA\Parameter(name: 'rideId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Nhận đơn thành công'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ'),
            new OA\Response(response: 403, description: 'Không đủ điều kiện nhận đơn'),
            new OA\Response(response: 422, description: 'Đơn không còn khả dụng hoặc driver đang bận'),
            new OA\Response(response: 404, description: 'Không tìm thấy đơn hàng'),
        ]
    )]
    public function acceptOrder(string $rideId, AcceptOrderRequest $request): JsonResponse
    {
        $result = $this->driverOperationService->acceptOrder(
            AcceptOrderDTO::fromRequest($request, $rideId)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/driver/ride/{rideId}/reject',
        summary: 'UC-33: Từ chối đơn hàng (Trước khi nhận)',
        security: [['sanctum' => []]],
        tags: ['Driver'],
        parameters: [
            new OA\Parameter(name: 'rideId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Từ chối thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy đơn hàng'),
            new OA\Response(response: 422, description: 'Đơn không ở trạng thái chờ'),
        ]
    )]
    public function rejectOrder(string $rideId, RejectOrderRequest $request): JsonResponse
    {
        $result = $this->driverOperationService->rejectOrder(
            RejectOrderDTO::fromRequest($request, $rideId)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/driver/ride/{rideId}/cancel',
        summary: 'UC-33: Hủy chuyến đi (Sau khi đã nhận)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reason_id'],
                properties: [
                    new OA\Property(
                        property: 'reason_id', 
                        description: 'ID lý do hủy chuyến. 1: Khách không ra (Customer No Show), 2: Xe hỏng (Vehicle Broken), 3: Đặt sai điểm (Wrong Location), 4: Khác (Other)', 
                        type: 'integer', 
                        example: 1
                    ),
                    new OA\Property(property: 'current_lat', type: 'number', format: 'float', example: 10.776889),
                    new OA\Property(property: 'current_lng', type: 'number', format: 'float', example: 106.700806),
                ]
            )
        ),
        tags: ['Driver'],
        parameters: [
            new OA\Parameter(name: 'rideId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hủy thành công'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ'),
            new OA\Response(response: 403, description: 'Không có quyền hủy hoặc bị phạt'),
            new OA\Response(response: 422, description: 'Trạng thái đơn không hợp lệ'),
        ]
    )]
    public function cancelOrder(string $rideId, CancelOrderRequest $request): JsonResponse
    {
        $result = $this->driverOperationService->cancelOrder(
            CancelOrderDTO::fromRequest($request, $rideId)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
