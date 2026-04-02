<?php

declare(strict_types=1);

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

    public function register(): void
    {
        // ── Repository Binding ─────────────────────────────────
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        // ── Services (singleton để tái sử dụng DI graph) ──────
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);
    }

    public function boot(): void
    {
       parent::boot();
    }


}
