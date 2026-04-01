<?php

declare(strict_types=1);

namespace App\Modules\User\Providers;

use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Services\Auth\LoginService;
use App\Modules\User\Services\Auth\LogoutService;
use App\Modules\User\Services\Auth\RegisterService;
use App\Modules\User\Services\Auth\SendOtpService;
use App\Modules\User\Services\Auth\VerifyOtpService;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Repository Binding ─────────────────────────────────
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        // ── Services (singleton để tái sử dụng DI graph) ──────
        $this->app->singleton(RegisterService::class);
        $this->app->singleton(LoginService::class);
        $this->app->singleton(LogoutService::class);
        $this->app->singleton(SendOtpService::class);
        $this->app->singleton(VerifyOtpService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migrations');
    }
}
