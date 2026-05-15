<?php

declare(strict_types=1);

namespace App\Modules\Notification\DTO;

use App\Modules\Notification\Http\Requests\GetNotificationsRequest;
use App\Modules\Notification\Model\Enums\NotificationCategory;

final class GetNotificationsDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly ?NotificationCategory $category = null,
        public readonly int $perPage = 20,
    ) {}

    public static function fromRequest(GetNotificationsRequest $request): self
    {
        $category = $request->input('category');
        
        return new self(
            userId: (string) $request->user()->id,
            category: $category ? NotificationCategory::tryFrom($category) : null,
            perPage: (int) $request->input('per_page', 20),
        );
    }
}
