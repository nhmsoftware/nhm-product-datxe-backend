<?php

declare(strict_types=1);

namespace App\Modules\User\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện xảy ra khi trạng thái tài khoản của người dùng bị thay đổi (Khóa/Mở khóa).
 * UC-78 Lock/Unlock User
 */
final class UserStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string|int $userId,
        public readonly bool       $isActive,
        public readonly ?string    $reason = null,
        public readonly ?string    $expiredAt = null,
    ) {
    }
}
