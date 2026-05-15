<?php

declare(strict_types=1);

namespace App\Modules\Notification\Interfaces;

use App\Modules\User\Model\User;

interface PushNotificationServiceInterface
{
    /**
     * Gửi push notification đến một user cụ thể (UC-127)
     * @param User $user
     * @param string $title
     * @param string $content
     * @param array $data Metadata đi kèm (deeplink, ride_id, ...)
     * @param string|null $icon
     */
    public function sendToUser(User $user, string $title, string $content, array $data = [], ?string $icon = null): bool;

    /**
     * Gửi push notification đến danh sách user (UC-127)
     */
    public function sendToMultipleUsers(array $userIds, string $title, string $content, array $data = []): int;

    /**
     * Gửi push notification đến tất cả users (Hệ thống/Admin - UC-127)
     */
    public function broadcast(string $title, string $content, array $data = []): bool;
}
