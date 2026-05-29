<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Core\Services\BaseService;
use App\Modules\Notification\Interfaces\PushNotificationServiceInterface;
use App\Modules\User\Model\User;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;

final class PushNotificationService extends BaseService implements PushNotificationServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly FirebaseCloudMessagingService $fcmService
    ) {}
    /**
     * Gửi push notification đến một user cụ thể (UC-127)
     */
    public function sendToUser(User $user, string $title, string $content, array $data = [], ?string $icon = null): bool
    {
        return $this->execute(function () use ($user, $title, $content, $data, $icon) {
            $devices = $user->userDevices()->whereNotNull('token')->get();

            if ($devices->isEmpty()) {
                Log::info("No device token found for user ID: {$user->id}. Skipping push.");
                return false;
            }

            foreach ($devices as $device) {
                $this->sendToDevice($device->token, $title, $content, $data, $icon);
            }

            return true;
        })->getData() ?? false;
    }

    /**
     * Gửi push notification đến danh sách user (UC-127)
     */
    public function sendToMultipleUsers(array $userIds, string $title, string $content, array $data = []): int
    {
        $count = 0;
        foreach ($userIds as $userId) {
            $user = $this->userRepository->findById($userId);
            if ($user && $this->sendToUser($user, $title, $content, $data)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Gửi push notification đến tất cả users (Hệ thống/Admin - UC-127)
     */
    public function broadcast(string $title, string $content, array $data = []): bool
    {
        // Trong thực tế sẽ dùng Topic Messaging của FCM hoặc OneSignal
        // Ở đây giả lập bằng cách chunk toàn bộ user qua repository
        $this->userRepository->chunkActiveUsers(100, function ($users) use ($title, $content, $data) {
            foreach ($users as $user) {
                $this->sendToUser($user, $title, $content, $data);
            }
        });

        return true;
    }

    /**
     * Gửi push notification đến một token thiết bị cụ thể
     */
    private function sendToDevice(string $token, string $title, string $content, array $data = [], ?string $icon = null): void
    {
        $success = $this->fcmService->send($token, $title, $content, $data);
        
        if ($success) {
            Log::info("PUSH NOTIFICATION SENT", [
                'token'   => $token,
                'title'   => $title
            ]);
        }
    }
}
