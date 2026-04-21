<?php

declare(strict_types=1);

namespace App\Modules\Communication\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Communication\DTO\SendMessageDTO;
use App\Modules\Communication\Events\ChatMessageSent;
use App\Modules\Communication\Interfaces\ChatMessageRepositoryInterface;
use App\Modules\Communication\Interfaces\CommunicationServiceInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;

final class CommunicationService extends BaseService implements CommunicationServiceInterface
{
    public function __construct(
        private readonly ChatMessageRepositoryInterface $chatMessageRepository,
        private readonly RideRepositoryInterface        $rideRepository,
        private readonly UserRepositoryInterface        $userRepository,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function sendMessage(SendMessageDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $ride = $this->rideRepository->find($dto->rideId);

            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);
            $this->validate($ride->driver_id !== null, 'Chuyến xe chưa có tài xế.', 400);

            // Kiểm tra quyền (phải là Customer hoặc Driver của chuyến đi)
            $isCustomer = (string) $ride->customer_id === (string) $dto->senderId;
            $isDriver   = (string) $ride->driver_id   === (string) $dto->senderId;
            $this->validate($isCustomer || $isDriver, 'Bạn không có quyền tham gia chat trong chuyến đi này.', 403);

            // UC-14 A7: Khóa chat sau khi kết thúc chuyến
            $this->validate(!$ride->status->isTerminal(), 'Không thể gửi tin nhắn sau khi kết thúc chuyến.', 400);

            $receiverId = $isCustomer ? $ride->driver_id : $ride->customer_id;

            $message = $this->chatMessageRepository->saveMessage([
                'ride_id'     => $dto->rideId,
                'sender_id'   => $dto->senderId,
                'receiver_id' => $receiverId,
                'message'     => $dto->message,
            ]);

            // Phát sự kiện Realtime
            event(new ChatMessageSent($message));

            return $message->toArray();
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function getChatHistory(string $rideId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($rideId, $userId) {
            $ride = $this->rideRepository->find($rideId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            $isParticipant = (string) $ride->customer_id === (string) $userId || (string) $ride->driver_id === (string) $userId;
            $this->validate($isParticipant, 'Bạn không có quyền xem lịch sử chat của chuyến đi này.', 403);

            $messages = $this->chatMessageRepository->getByRideId($rideId);

            return $messages->toArray();
        });
    }

    /**
     * @inheritDoc
     */
    public function initiateCall(string $rideId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($rideId, $userId) {
            $ride = $this->rideRepository->find($rideId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);
            $this->validate($ride->driver_id !== null, 'Chuyến xe chưa có tài xế nhận.', 400);

            $isCustomer = (string) $ride->customer_id === (string) $userId;
            $isDriver   = (string) $ride->driver_id   === (string) $userId;
            $this->validate($isCustomer || $isDriver, 'Bạn không có quyền gọi điện trong chuyến đi này.', 403);

            // Tìm thông tin đối phương
            $targetUserId = $isCustomer ? $ride->driver_id : $ride->customer_id;
            $targetUser   = $this->userRepository->find($targetUserId);
            $this->validate($targetUser !== null, 'Không tìm thấy thông tin đối phương.', 404);

            return [
                'ride_id'      => $rideId,
                'target_phone' => $targetUser->phone,
                'target_role'  => $isCustomer ? 'Driver' : 'Customer',
            ];
        });
    }
}
