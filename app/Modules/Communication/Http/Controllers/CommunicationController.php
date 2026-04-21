<?php

declare(strict_types=1);

namespace App\Modules\Communication\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Communication\DTO\SendMessageDTO;
use App\Modules\Communication\Http\Requests\SendMessageRequest;
use App\Modules\Communication\Interfaces\CommunicationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CommunicationController extends BaseController
{
    public function __construct(
        private readonly CommunicationServiceInterface $communicationService
    ) {
    }

    /**
     * Gửi tin nhắn chat.
     */
    public function sendMessage(string $rideId, SendMessageRequest $request): JsonResponse
    {
        $result = $this->communicationService->sendMessage(
            SendMessageDTO::fromRequest($request, $rideId)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    /**
     * Lấy lịch sử chat.
     */
    public function getChatHistory(string $rideId, Request $request): JsonResponse
    {
        $result = $this->communicationService->getChatHistory($rideId, (string) $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    /**
     * Khởi tạo cuộc gọi.
     */
    public function initiateCall(string $rideId, Request $request): JsonResponse
    {
        $result = $this->communicationService->initiateCall($rideId, (string) $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
