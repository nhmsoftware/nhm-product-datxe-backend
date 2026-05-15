<?php

declare(strict_types=1);

namespace App\Modules\Notification\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Notification\Model\Notification;
use Illuminate\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a notification for a user (UC-128)
     */
    public function findForUser(string $id, string $userId): ?Notification;

    /**
     * Get paginated notifications for a user, optionally filtered by category (UC-126)
     */
    public function getForUser(string $userId, ?string $category, int $perPage): LengthAwarePaginator;

    /**
     * Mark a specific notification as read for a user (UC-126)
     */
    public function markAsRead(string $id, string $userId): bool;

    /**
     * Mark all notifications as read for a user (UC-126)
     */
    public function markAllAsRead(string $userId): int;

    /**
     * Delete a notification for a user (UC-126)
     */
    public function deleteForUser(string $id, string $userId): bool;

    /**
     * Get unread count for a user
     */
    public function getUnreadCount(string $userId): int;
}
