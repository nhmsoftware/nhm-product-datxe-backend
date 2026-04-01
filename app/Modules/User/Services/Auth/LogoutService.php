<?php

declare(strict_types=1);

namespace App\Modules\User\Services\Auth;

use App\Modules\User\Model\User;

final class LogoutService
{
    /**
     * Thu hồi token hiện tại (hoặc tất cả nếu logout_all = true).
     */
    public function handle(User $user, bool $logoutAll = false): void
    {
        if ($logoutAll) {
            $user->tokens()->delete();
        } else {
            $user->currentAccessToken()->delete();
        }
    }
}
