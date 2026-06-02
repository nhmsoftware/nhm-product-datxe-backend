<?php

declare(strict_types=1);

namespace App\Modules\Notification\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Notification\Interfaces\NotificationRepositoryInterface;
use App\Modules\Notification\Model\Notification;
use Illuminate\Pagination\LengthAwarePaginator;

final class NotificationRepository extends BaseRepository implements NotificationRepositoryInterface
{
    public function getModel(): string
    {
        return Notification::class;
    }

    public function findForUser(string $id, string $userId): ?Notification
    {
        /** @var Notification|null */
        return $this->getQuery()
            ->where('id', $id)
            ->where('notifiable_id', $userId)
            ->first();
    }

    public function getForUser(string $userId, ?string $category, int $perPage): LengthAwarePaginator
    {
        $query = $this->getQuery()
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', 'App\Modules\User\Model\User') // Standard Laravel morph type
            ->latest();

        if ($category) {
            $query->where('category', $category);
        }

        return $query->paginate($perPage);
    }

    public function markAsRead(string $id, string $userId): bool
    {
        return (bool) $this->getQuery()
            ->where('id', $id)
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markAllAsRead(string $userId): int
    {
        return $this->getQuery()
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function deleteForUser(string $id, string $userId): bool
    {
        return (bool) $this->getQuery()
            ->where('id', $id)
            ->where('notifiable_id', $userId)
            ->delete();
    }

    public function getUnreadCount(string $userId): int
    {
        return $this->getQuery()
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
