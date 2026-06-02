<?php

declare(strict_types=1);

namespace App\Modules\Ride\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Ride\Interfaces\RideChatMessageRepositoryInterface;
use App\Modules\Ride\Model\Enums\RideChatMessageStatus;
use App\Modules\Ride\Model\Enums\RideChatSenderType;
use App\Modules\Ride\Model\RideChatMessage;
use Illuminate\Support\Collection;

final class RideChatMessageRepository extends BaseRepository implements RideChatMessageRepositoryInterface
{
    public function getModel(): string
    {
        return RideChatMessage::class;
    }

    public function getConversationByRideId(string $rideId): Collection
    {
        return $this->getQuery()
            ->with('sender')
            ->where('ride_id', $rideId)
            ->orderBy('created_at')
            ->get();
    }

    public function storeRideChatMessage(
        string $rideId,
        string $senderId,
        RideChatSenderType $senderType,
        string $message,
        RideChatMessageStatus $status
    ): RideChatMessage {
        /** @var RideChatMessage $messageModel */
        $messageModel = $this->create([
            'ride_id' => $rideId,
            'sender_id' => $senderId,
            'sender_type' => $senderType->value,
            'message' => $message,
            'status' => $status->value,
        ]);

        return $messageModel->load('sender');
    }
}
