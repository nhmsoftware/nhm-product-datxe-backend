<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Notification\DTO\GetNotificationsDTO;
use App\Modules\Notification\DTO\UpdateDeviceTokenDTO;
use App\Modules\Notification\Events\NotificationReadStatusUpdated;
use App\Modules\Notification\Interfaces\NotificationRepositoryInterface;
use App\Modules\Notification\Interfaces\NotificationServiceInterface;

final class NotificationService extends BaseService implements NotificationServiceInterface
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly \App\Modules\User\Interfaces\UserRepositoryInterface $userRepository,
    ) {}

    public function getNotifications(GetNotificationsDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $notifications = $this->notificationRepository->getForUser(
                $dto->userId,
                $dto->category?->value,
                $dto->perPage
            );

            return [
                'items' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage(),
                ],
                'unread_count' => $this->notificationRepository->getUnreadCount($dto->userId),
            ];
        });
    }

    public function markAsRead(string $id, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($id, $userId) {
            $notification = $this->notificationRepository->findForUser($id, $userId);
            $this->validate($notification !== null, 'Không tìm thấy thông báo.', 404);

            // A1: Nếu đã đọc trước đó -> không cập nhật, vẫn trả về thành công
            if ($notification->read_at !== null) {
                return ['unread_count' => $this->notificationRepository->getUnreadCount($userId)];
            }

            $success = $this->notificationRepository->markAsRead($id, $userId);
            $this->validate($success, 'Không thể cập nhật trạng thái thông báo.', 500);

            $unreadCount = $this->notificationRepository->getUnreadCount($userId);
            event(new NotificationReadStatusUpdated($userId, $unreadCount));

            return ['unread_count' => $unreadCount];
        }, useTransaction: true);
    }

    public function markAllAsRead(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $this->notificationRepository->markAllAsRead($userId);

            $unreadCount = 0;
            event(new NotificationReadStatusUpdated($userId, $unreadCount));

            return ['unread_count' => $unreadCount];
        }, useTransaction: true);
    }

    public function deleteNotification(string $id, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($id, $userId) {
            $success = $this->notificationRepository->deleteForUser($id, $userId);
            $this->validate($success, 'Không tìm thấy thông báo.', 404);

            $unreadCount = $this->notificationRepository->getUnreadCount($userId);
            event(new NotificationReadStatusUpdated($userId, $unreadCount));

            return ['unread_count' => $unreadCount];
        }, useTransaction: true);
    }

    public function updateDeviceToken(UpdateDeviceTokenDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy người dùng.', 404);

            $this->userRepository->upsertDevice($user, [
                'device_id'   => $dto->deviceId,
                'token'       => $dto->deviceToken,
                'device_type' => $dto->deviceType,
            ]);

            return true;
        }, useTransaction: true);
    }
}
