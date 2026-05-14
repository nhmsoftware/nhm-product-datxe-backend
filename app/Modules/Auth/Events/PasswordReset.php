<?php

declare(strict_types=1);

namespace App\Modules\Auth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện mật khẩu người dùng đã được đặt lại (UC-03).
 */
final class PasswordReset
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $occurredAt,
    ) {
    }
}
