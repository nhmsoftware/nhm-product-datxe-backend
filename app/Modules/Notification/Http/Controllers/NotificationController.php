<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Notification\DTO\GetNotificationsDTO;
use App\Modules\Notification\Http\Requests\GetNotificationsRequest;
use App\Modules\Notification\Interfaces\NotificationServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class NotificationController extends BaseController
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService,
    ) {}

    #[OA\Get(
        path: '/api/v1/notifications',
        summary: 'Xem danh sách thông báo (UC-126)',
        security: [['sanctum' => []]],
        tags: ['Notification'],
        parameters: [
            new OA\Parameter(
                name: 'category',
                in: 'query',
                required: false,
                description: 'Lọc theo nhóm: promotion, order, system',
                schema: new OA\Schema(type: 'string', enum: ['promotion', 'order', 'system'])
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Số lượng item mỗi trang',
                schema: new OA\Schema(type: 'integer', default: 20)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
        ]
    )]
    public function index(GetNotificationsRequest $request): JsonResponse
    {
        $result = $this->notificationService->getNotifications(GetNotificationsDTO::fromRequest($request));
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/notifications/{id}/read',
        summary: 'Đánh dấu đã đọc một thông báo (UC-128)',
        security: [['sanctum' => []]],
        tags: ['Notification'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của thông báo',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy thông báo'),
        ]
    )]
    public function markAsRead(string $id): JsonResponse
    {
        $result = $this->notificationService->markAsRead($id, (string) auth()->id());

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/notifications/read-all',
        summary: 'Đánh dấu đã đọc tất cả thông báo (UC-126)',
        security: [['sanctum' => []]],
        tags: ['Notification'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function markAllAsRead(): JsonResponse
    {
        $result = $this->notificationService->markAllAsRead((string) auth()->id());

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/notifications/{id}',
        summary: 'Xóa thông báo (UC-126)',
        security: [['sanctum' => []]],
        tags: ['Notification'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của thông báo',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy thông báo'),
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $result = $this->notificationService->deleteNotification($id, (string) auth()->id());

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/notifications/update-token',
        summary: 'Cập nhật Push Token thiết bị (UC-127)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['device_id', 'device_token'],
                properties: [
                    new OA\Property(property: 'device_id', type: 'string', example: 'abc123'),
                    new OA\Property(property: 'device_token', type: 'string', example: 'fcm_token_here'),
                    new OA\Property(property: 'device_type', type: 'string', example: 'android'),
                ]
            )
        ),
        tags: ['Notification'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
        ]
    )]
    public function updateDeviceToken(\App\Modules\Notification\Http\Requests\UpdateDeviceTokenRequest $request): JsonResponse
    {
        $result = $this->notificationService->updateDeviceToken(\App\Modules\Notification\DTO\UpdateDeviceTokenDTO::fromRequest($request));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
