<?php

declare(strict_types=1);

namespace App\Modules\User\Events;

use App\Modules\User\Model\User;

class UserRegistered
{
    public function __construct(
        public readonly User $user,
    ) {}
}
