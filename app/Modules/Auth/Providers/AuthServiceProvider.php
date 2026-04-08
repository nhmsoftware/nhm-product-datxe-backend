<?php

declare(strict_types=1);

namespace App\Modules\Auth\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Auth\Services\AuthService;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Repositories\UserRepository;

class AuthServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
       return 'Auth';
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
