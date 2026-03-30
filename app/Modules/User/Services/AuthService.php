<?php

namespace App\Modules\User\Services;

use App\Core\Services\BaseService;
use App\Modules\User\Interfaces\AuthServiceInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;

class AuthService extends BaseService implements AuthServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
    )
    {
    }
}
