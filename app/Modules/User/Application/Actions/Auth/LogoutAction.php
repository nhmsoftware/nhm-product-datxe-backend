<?php

declare(strict_types=1);

namespace Modules\User\Application\Actions\Auth;

use Modules\User\Domain\Entities\User;

final class LogoutAction
{
    /**
     * Thu hồi token hiện tại (hoặc tất cả nếu logout_all = true).
     */
    public function execute(User $user, bool $logoutAll = false): void
    {
        if ($logoutAll) {
            $user->tokens()->delete();
        } else {
            $user->currentAccessToken()->delete();
        }
    }
}
