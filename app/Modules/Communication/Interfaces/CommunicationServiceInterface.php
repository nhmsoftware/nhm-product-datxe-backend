<?php

declare(strict_types=1);

namespace App\Modules\Communication\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Communication\DTO\SendMessageDTO;

interface CommunicationServiceInterface
{
    /**
     * Gửi tin nhắn chat trong chuyến xe (UC-14).
     */
    public function sendMessage(SendMessageDTO $dto): ServiceReturn;

    /**
     * Lấy lịch sử chat của một chuyến xe.
     */
    public function getChatHistory(string $rideId, string $userId): ServiceReturn;

    /**
     * Khởi tạo cuộc gọi tới đối phương (UC-14).
     */
    public function initiateCall(string $rideId, string $userId): ServiceReturn;
}
