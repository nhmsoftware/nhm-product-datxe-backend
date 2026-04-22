<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Ride\Model\Enums\RideChatMessageStatus;
use App\Modules\Ride\Model\Enums\RideChatSenderType;
use App\Modules\Ride\Model\RideChatMessage;
use Illuminate\Support\Collection;

interface RideChatMessageRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy toàn bộ hội thoại của một chuyến đi để hiển thị chat realtime (UC-14).
     *
     * @param string $rideId
     * @return Collection<int, RideChatMessage>
     */
    public function getConversationByRideId(string $rideId): Collection;

    /**
     * Lưu một tin nhắn chat mới của customer hoặc driver (UC-14 bước 5, 6).
     *
     * @param string $rideId
     * @param string $senderId
     * @param RideChatSenderType $senderType
     * @param string $message
     * @param RideChatMessageStatus $status
     * @return RideChatMessage
     */
    public function storeRideChatMessage(
        string $rideId,
        string $senderId,
        RideChatSenderType $senderType,
        string $message,
        RideChatMessageStatus $status
    ): RideChatMessage;
}
