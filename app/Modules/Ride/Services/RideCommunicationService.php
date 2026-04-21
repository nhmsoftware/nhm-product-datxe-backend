<?php

declare(strict_types=1);

namespace App\Modules\Ride\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Ride\DTO\InitiateRideCallDTO;
use App\Modules\Ride\DTO\SendRideChatMessageDTO;
use App\Modules\Ride\DTO\ShowRideConversationDTO;
use App\Modules\Ride\DTO\UpdateRideCallStatusDTO;
use App\Modules\Ride\Interfaces\RideCallLogRepositoryInterface;
use App\Modules\Ride\Interfaces\RideChatMessageRepositoryInterface;
use App\Modules\Ride\Interfaces\RideCommunicationRealtimeInterface;
use App\Modules\Ride\Interfaces\RideCommunicationServiceInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Model\Enums\RideCallStatus;
use App\Modules\Ride\Model\Enums\RideChatMessageStatus;
use App\Modules\Ride\Model\Enums\RideChatSenderType;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Ride;
use App\Modules\Ride\Model\RideCallLog;
use App\Modules\Ride\Model\RideChatMessage;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\User;
use Illuminate\Support\Collection;

final class RideCommunicationService extends BaseService implements RideCommunicationServiceInterface
{
    public function __construct(
        private readonly RideRepositoryInterface $rideRepository,
        private readonly RideChatMessageRepositoryInterface $rideChatMessageRepository,
        private readonly RideCallLogRepositoryInterface $rideCallLogRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly RideCommunicationRealtimeInterface $rideCommunicationRealtime
    ) {
    }

    public function getConversation(ShowRideConversationDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            [$ride, $actorType, $counterpart] = $this->resolveRideParticipants($dto->rideId, $dto->actorId);

            /** @var Collection<int, RideChatMessage> $messages */
            $messages = $this->rideChatMessageRepository->getConversationByRideId($dto->rideId);

            return [
                'ride' => [
                    'id' => $ride->id,
                    'status' => $ride->status->value,
                    'status_label' => $ride->status->getLabel(),
                ],
                'actor' => [
                    'id' => $dto->actorId,
                    'type' => $actorType->value,
                    'type_label' => $actorType->getLabel(),
                ],
                'contact' => [
                    'id' => $counterpart->id,
                    'name' => $counterpart->full_name,
                    'phone' => $counterpart->phone,
                ],
                'chat_enabled' => !$ride->status->isTerminal(),
                'messages' => $messages->map(fn (RideChatMessage $message): array => $this->mapMessage($message))->all(),
            ];
        });
    }

    public function sendMessage(SendRideChatMessageDTO $dto): ServiceReturn
    {
        $createdMessageId = null;

        return $this->execute(
            function () use ($dto, &$createdMessageId): array {
                [$ride, $actorType, $counterpart] = $this->resolveRideParticipants($dto->rideId, $dto->actorId);

                $this->validate(
                    !$ride->status->isTerminal(),
                    $ride->status === RideStatus::COMPLETED
                        ? 'Không thể gửi tin nhắn sau khi kết thúc chuyến.'
                        : 'Không thể gửi tin nhắn cho chuyến xe đã bị hủy.',
                    409
                );

                $message = $this->rideChatMessageRepository->storeRideChatMessage(
                    rideId: $ride->id,
                    senderId: $dto->actorId,
                    senderType: $actorType,
                    message: $dto->message,
                    status: RideChatMessageStatus::SENT
                );
                $createdMessageId = $message->id;

                return [
                    'message' => $this->mapMessage($message),
                    'contact' => [
                        'id' => $counterpart->id,
                        'name' => $counterpart->full_name,
                        'phone' => $counterpart->phone,
                    ],
                ];
            },
            useTransaction: true,
            afterCommitCallback: function () use ($dto, &$createdMessageId): void {
                [$ride, $actorType] = $this->resolveRideParticipants($dto->rideId, $dto->actorId);
                if ($createdMessageId === null) {
                    return;
                }

                /** @var RideChatMessage|null $latestMessage */
                $latestMessage = $this->rideChatMessageRepository->findById($createdMessageId, relations: ['sender']);
                if (!$latestMessage instanceof RideChatMessage) {
                    return;
                }

                $this->rideCommunicationRealtime->publish([
                    'event' => 'communication.chat.message.sent',
                    'ride_id' => $ride->id,
                    'room' => sprintf('ride:%d', $ride->id),
                    'sender_type' => $actorType->value,
                    'sender_type_label' => $actorType->getLabel(),
                    'message' => $this->mapMessage($latestMessage),
                    'occurred_at' => $latestMessage->created_at?->toIso8601String() ?? now()->toIso8601String(),
                ]);
            }
        );
    }

    public function initiateCall(InitiateRideCallDTO $dto): ServiceReturn
    {
        $createdCallId = null;

        return $this->execute(
            function () use ($dto, &$createdCallId): array {
                [$ride, $actorType, $counterpart] = $this->resolveRideParticipants($dto->rideId, $dto->actorId);

                $this->validate(
                    in_array($ride->status, [RideStatus::ACCEPTED, RideStatus::IN_PROGRESS], strict: true),
                    'Không thể thực hiện cuộc gọi cho chuyến xe ở trạng thái hiện tại.',
                    409
                );
                $this->validate(!empty($counterpart->phone), 'Không thể thực hiện cuộc gọi.', 409);

                $callLog = $this->rideCallLogRepository->createRideCallAttempt(
                    rideId: $ride->id,
                    callerId: $dto->actorId,
                    calleeId: $counterpart->id,
                    callerType: $actorType,
                    status: RideCallStatus::INITIATED
                );
                $createdCallId = $callLog->id;

                return $this->mapCall($callLog, $counterpart);
            },
            useTransaction: true,
            afterCommitCallback: function () use ($dto, &$createdCallId): void {
                [$ride, $actorType, $counterpart] = $this->resolveRideParticipants($dto->rideId, $dto->actorId);
                if ($createdCallId === null) {
                    return;
                }

                /** @var RideCallLog|null $callLog */
                $callLog = $this->rideCallLogRepository->findById($createdCallId);
                if (!$callLog instanceof RideCallLog) {
                    return;
                }

                $this->rideCommunicationRealtime->publish([
                    'event' => 'communication.call.initiated',
                    'ride_id' => $ride->id,
                    'room' => sprintf('ride:%d', $ride->id),
                    'caller_type' => $actorType->value,
                    'caller_type_label' => $actorType->getLabel(),
                    'call' => $this->mapCall($callLog, $counterpart),
                    'occurred_at' => $callLog->created_at?->toIso8601String() ?? now()->toIso8601String(),
                ]);
            }
        );
    }

    public function updateCallStatus(UpdateRideCallStatusDTO $dto): ServiceReturn
    {
        return $this->execute(
            function () use ($dto): array {
                [$ride] = $this->resolveRideParticipants($dto->rideId, $dto->actorId);

                $callLog = $this->rideCallLogRepository->findRideCallByIdAndRide($dto->rideId, $dto->callId);
                $this->validate($callLog !== null, 'Không tìm thấy lịch sử cuộc gọi.', 404);
                $this->validate(
                    !$callLog->status->isTerminal(),
                    'Cuộc gọi đã kết thúc và không thể cập nhật thêm.',
                    409
                );
                $this->validate(
                    $callLog->status->canTransitionTo($dto->status),
                    'Không thể cập nhật trạng thái cuộc gọi.',
                    409
                );

                $updated = $this->rideCallLogRepository->updateRideCallStatus($dto->callId, $dto->status, $dto->failureReason);
                $this->validate($updated, 'Không thể cập nhật trạng thái cuộc gọi.', 500);

                /** @var RideCallLog|null $updatedCall */
                $updatedCall = $this->rideCallLogRepository->findRideCallByIdAndRide($dto->rideId, $dto->callId);
                $this->validate($updatedCall !== null, 'Không thể tải lại cuộc gọi vừa cập nhật.', 500);

                /** @var User|null $callee */
                $callee = $this->userRepository->findById($updatedCall->callee_id);
                $this->validate($callee !== null, 'Không tìm thấy người nhận cuộc gọi.', 404);

                return [
                    'ride_id' => $ride->id,
                    'call' => $this->mapCall($updatedCall, $callee),
                ];
            },
            useTransaction: true,
            afterCommitCallback: function () use ($dto): void {
                $callLog = $this->rideCallLogRepository->findRideCallByIdAndRide($dto->rideId, $dto->callId);
                if (!$callLog instanceof RideCallLog) {
                    return;
                }

                /** @var User|null $callee */
                $callee = $this->userRepository->findById($callLog->callee_id);
                if (!$callee instanceof User) {
                    return;
                }

                $this->rideCommunicationRealtime->publish([
                    'event' => 'communication.call.status.updated',
                    'ride_id' => $dto->rideId,
                    'room' => sprintf('ride:%d', $dto->rideId),
                    'call' => $this->mapCall($callLog, $callee),
                    'occurred_at' => now()->toIso8601String(),
                ]);
            }
        );
    }

    /**
     * @return array{0: Ride, 1: RideChatSenderType, 2: User}
     */
    private function resolveRideParticipants(int $rideId, int $actorId): array
    {
        /** @var Ride|null $ride */
        $ride = $this->rideRepository->findById($rideId, relations: ['customer', 'driver']);
        $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);
        $this->validate($ride->driver_id !== null, 'Chuyến đi hiện chưa có tài xế nhận.', 409);

        if ($ride->customer_id === $actorId) {
            /** @var User|null $counterpart */
            $counterpart = $ride->driver;
            $this->validate($counterpart !== null, 'Không tìm thấy tài xế của chuyến đi.', 404);

            return [$ride, RideChatSenderType::CUSTOMER, $counterpart];
        }

        if ($ride->driver_id === $actorId) {
            /** @var User|null $counterpart */
            $counterpart = $ride->customer;
            $this->validate($counterpart !== null, 'Không tìm thấy khách hàng của chuyến đi.', 404);

            return [$ride, RideChatSenderType::DRIVER, $counterpart];
        }

        $this->throw('Bạn không có quyền liên hệ trong chuyến đi này.', 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMessage(RideChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'ride_id' => $message->ride_id,
            'sender_id' => $message->sender_id,
            'sender_type' => $message->sender_type->value,
            'sender_type_label' => $message->sender_type->getLabel(),
            'sender_name' => $message->sender?->full_name,
            'content' => $message->message,
            'status' => $message->status->value,
            'status_label' => $message->status->getLabel(),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCall(RideCallLog $callLog, User $callee): array
    {
        return [
            'id' => $callLog->id,
            'ride_id' => $callLog->ride_id,
            'caller_id' => $callLog->caller_id,
            'callee_id' => $callLog->callee_id,
            'caller_type' => $callLog->caller_type->value,
            'caller_type_label' => $callLog->caller_type->getLabel(),
            'status' => $callLog->status->value,
            'status_label' => $callLog->status->getLabel(),
            'callee_name' => $callee->full_name,
            'callee_phone' => $callee->phone,
            'failure_reason' => $callLog->failure_reason,
            'created_at' => $callLog->created_at?->toIso8601String(),
            'updated_at' => $callLog->updated_at?->toIso8601String(),
        ];
    }
}
