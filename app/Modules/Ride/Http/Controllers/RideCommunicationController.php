<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Ride\DTO\InitiateRideCallDTO;
use App\Modules\Ride\DTO\SendRideChatMessageDTO;
use App\Modules\Ride\DTO\ShowRideConversationDTO;
use App\Modules\Ride\DTO\UpdateRideCallStatusDTO;
use App\Modules\Ride\Http\Requests\InitiateRideCallRequest;
use App\Modules\Ride\Http\Requests\SendRideChatMessageRequest;
use App\Modules\Ride\Http\Requests\ShowRideConversationRequest;
use App\Modules\Ride\Http\Requests\UpdateRideCallStatusRequest;
use App\Modules\Ride\Interfaces\RideCommunicationServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class RideCommunicationController extends BaseController
{
    public function __construct(
        private readonly RideCommunicationServiceInterface $rideCommunicationService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/ride/{rideId}/communication/messages',
        description: 'Trả về toàn bộ cuộc trò chuyện và trạng thái liên hệ của một chuyến đi đã có tài xế nhận.',
        summary: 'Lấy hội thoại customer-driver (UC-14)',
        security: [['sanctum' => []]],
        tags: ['Ride Communication']
    )]
    #[OA\Parameter(name: 'rideId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lấy hội thoại thành công')]
    public function index(int $rideId, ShowRideConversationRequest $request): JsonResponse
    {
        $result = $this->rideCommunicationService->getConversation(
            ShowRideConversationDTO::fromRequest($request, $rideId)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy hội thoại thành công.');
    }

    #[OA\Post(
        path: '/api/v1/ride/{rideId}/communication/messages',
        description: 'Lưu tin nhắn chat trong chuyến đi và phát realtime tới room của ride.',
        summary: 'Gửi tin nhắn chat cho tài xế hoặc khách hàng (UC-14)',
        security: [['sanctum' => []]],
        tags: ['Ride Communication']
    )]
    #[OA\Parameter(name: 'rideId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['message'],
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Anh/chị đang tới đâu rồi ạ?'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Gửi tin nhắn thành công')]
    public function send(int $rideId, SendRideChatMessageRequest $request): JsonResponse
    {
        $result = $this->rideCommunicationService->sendMessage(
            SendRideChatMessageDTO::fromRequest($request, $rideId)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Gửi tin nhắn thành công.');
    }

    #[OA\Post(
        path: '/api/v1/ride/{rideId}/communication/calls',
        description: 'Tạo log cuộc gọi và trả thông tin liên hệ bên nhận để app có thể thực hiện cuộc gọi.',
        summary: 'Khởi tạo cuộc gọi customer-driver (UC-14)',
        security: [['sanctum' => []]],
        tags: ['Ride Communication']
    )]
    #[OA\Parameter(name: 'rideId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Khởi tạo cuộc gọi thành công')]
    public function initiateCall(int $rideId, InitiateRideCallRequest $request): JsonResponse
    {
        $result = $this->rideCommunicationService->initiateCall(
            InitiateRideCallDTO::fromRequest($request, $rideId)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Khởi tạo cuộc gọi thành công.');
    }

    #[OA\Post(
        path: '/api/v1/ride/{rideId}/communication/calls/{callId}/status',
        summary: 'Cập nhật trạng thái cuộc gọi (UC-14)',
        description: 'Dùng để phản ánh nhánh lỗi gọi, không phản hồi hoặc kết thúc cuộc gọi.',
        security: [['sanctum' => []]],
        tags: ['Ride Communication']
    )]
    #[OA\Parameter(name: 'rideId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'callId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['status'],
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 5),
                new OA\Property(property: 'failure_reason', type: 'string', example: 'Không thể thực hiện cuộc gọi.'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Cập nhật trạng thái cuộc gọi thành công')]
    public function updateCallStatus(int $rideId, int $callId, UpdateRideCallStatusRequest $request): JsonResponse
    {
        $result = $this->rideCommunicationService->updateCallStatus(
            UpdateRideCallStatusDTO::fromRequest($request, $rideId, $callId)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Cập nhật trạng thái cuộc gọi thành công.');
    }
}
