<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Ride\DTO\InitiateRideCallDTO;
use App\Modules\Ride\DTO\SendRideChatMessageDTO;
use App\Modules\Ride\DTO\ShowRideConversationDTO;
use App\Modules\Ride\DTO\UpdateRideCallStatusDTO;

interface RideCommunicationServiceInterface
{
    /**
     * Lấy toàn bộ hội thoại và trạng thái chat/call của ride (UC-14).
     *
     * @param ShowRideConversationDTO $dto
     * @return ServiceReturn
     */
    public function getConversation(ShowRideConversationDTO $dto): ServiceReturn;

    /**
     * Gửi một tin nhắn chat giữa customer và driver (UC-14 bước 5, 6).
     *
     * @param SendRideChatMessageDTO $dto
     * @return ServiceReturn
     */
    public function sendMessage(SendRideChatMessageDTO $dto): ServiceReturn;

    /**
     * Khởi tạo một cuộc gọi giữa customer và driver (UC-14 bước 8, 9).
     *
     * @param InitiateRideCallDTO $dto
     * @return ServiceReturn
     */
    public function initiateCall(InitiateRideCallDTO $dto): ServiceReturn;

    /**
     * Cập nhật trạng thái cuộc gọi để phản ánh fail/no-answer/completed (UC-14 A3, A4).
     *
     * @param UpdateRideCallStatusDTO $dto
     * @return ServiceReturn
     */
    public function updateCallStatus(UpdateRideCallStatusDTO $dto): ServiceReturn;
}
