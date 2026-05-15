<?php

declare(strict_types=1);

namespace App\Modules\Notification\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Notification\DTO\GetNotificationsDTO;

interface NotificationServiceInterface
{
    /**
     * Get list of notifications for user (UC-126)
     */
    public function getNotifications(GetNotificationsDTO $dto): ServiceReturn;

    /**
     * Mark a notification as read (UC-126)
     */
    public function markAsRead(string $id, string $userId): ServiceReturn;

    /**
     * Mark all notifications as read (UC-126)
     */
    public function markAllAsRead(string $userId): ServiceReturn;

    /**
     * Delete a notification (UC-126)
     */
    public function deleteNotification(string $id, string $userId): ServiceReturn;

    /**
     * Cập nhật token thiết bị để nhận push (UC-127)
     */
    public function updateDeviceToken(\App\Modules\Notification\DTO\UpdateDeviceTokenDTO $dto): ServiceReturn;
}
