<?php

declare(strict_types=1);

namespace App\Modules\Auth\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Repositories\UserRepository;
use App\Modules\Auth\Services\AuthService;

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
            AuthRepositoryInterface::class,
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
