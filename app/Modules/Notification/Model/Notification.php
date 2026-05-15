<?php

declare(strict_types=1);

namespace App\Modules\Notification\Model;

use App\Modules\Notification\Model\Enums\NotificationCategory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\DatabaseNotification;

/**
 * @property string $id
 * @property string $type
 * @property string $notifiable_type
 * @property string $notifiable_id
 * @property array $data
 * @property string|null $category
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Notification extends DatabaseNotification
{
    use SoftDeletes;

    protected $table = 'notifications';

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'category' => NotificationCategory::class,
    ];

    /**
     * Get title from data
     */
    public function getTitle(): string
    {
        return $this->data['title'] ?? '';
    }

    /**
     * Get content from data
     */
    public function getContent(): string
    {
        return $this->data['content'] ?? '';
    }

    /**
     * Get icon from data
     */
    public function getIcon(): string
    {
        return $this->data['icon'] ?? '';
    }
}
