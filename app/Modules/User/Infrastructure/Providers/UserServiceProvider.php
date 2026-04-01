<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\User\Application\Actions\Auth\LoginAction;
use Modules\User\Application\Actions\Auth\LogoutAction;
use Modules\User\Application\Actions\Auth\RegisterAction;
use Modules\User\Application\Actions\Auth\SendOtpAction;
use Modules\User\Application\Actions\Auth\VerifyOtpAction;
use Modules\User\Domain\Interfaces\UserRepositoryInterface;
use Modules\User\Infrastructure\Persistence\EloquentUserRepository;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Repository Binding ─────────────────────────────────
        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        // ── Actions (singleton để tái sử dụng DI graph) ───────
        $this->app->singleton(RegisterAction::class);
        $this->app->singleton(LoginAction::class);
        $this->app->singleton(LogoutAction::class);
        $this->app->singleton(SendOtpAction::class);
        $this->app->singleton(VerifyOtpAction::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migrations');
    }
}
