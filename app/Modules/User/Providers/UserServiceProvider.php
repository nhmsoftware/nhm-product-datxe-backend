<?php

namespace App\Modules\User\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\User\Interfaces\AuthServiceInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Services\AuthService;

class UserServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'User';
    }


    /**
     *
     * @return void
     */
    public function register(): void
    {
        /**
         * Repository
         */
        $this->app->singleton(UserRepositoryInterface::class, UserRepository::class);

        /**
         * Service
         */
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);
    }

    public function boot(): void
    {
        parent::boot();

        /**
         * Boot service in here
         */
    }
}
