<?php

declare(strict_types=1);

namespace App\Modules\User\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\User\Interfaces\ProfileRepositoryInterface;
use App\Modules\User\Interfaces\ProfileServiceInterface;
use App\Modules\User\Interfaces\SavedAddressRepositoryInterface;
use App\Modules\User\Interfaces\SavedAddressServiceInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Repositories\ProfileRepository;
use App\Modules\User\Repositories\SavedAddressRepository;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Services\ProfileService;
use App\Modules\User\Services\SavedAddressService;

class UserServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'User';
    }

    public function register(): void
    {
        // ── Services (singleton để tái sử dụng DI graph) ──────
        $this->app->singleton(ProfileServiceInterface::class, ProfileService::class);
        $this->app->singleton(SavedAddressServiceInterface::class, SavedAddressService::class);

        // ── Repositories (singleton) ────────────────────────
        $this->app->singleton(SavedAddressRepositoryInterface::class, SavedAddressRepository::class);
        $this->app->singleton(ProfileRepositoryInterface::class, ProfileRepository::class);
        $this->app->singleton(UserRepositoryInterface::class, UserRepository::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
