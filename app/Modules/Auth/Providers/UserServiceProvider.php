<?php

declare(strict_types=1);

namespace App\Modules\Auth\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Auth\Interfaces\UserRepositoryInterface;
use App\Modules\Auth\Repositories\UserRepository;
use App\Modules\Auth\Services\AuthService;

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
