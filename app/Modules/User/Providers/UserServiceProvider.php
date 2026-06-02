<?php

declare(strict_types=1);

namespace App\Modules\User\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\User\Http\Middleware\CheckAccountStatus;
use App\Modules\User\Interfaces\AdminDriverServiceInterface;
use App\Modules\User\Interfaces\AdminUserServiceInterface;
use App\Modules\User\Interfaces\MerchantProfileRepositoryInterface;
use App\Modules\User\Interfaces\ProfileRepositoryInterface;
use App\Modules\User\Interfaces\ProfileServiceInterface;
use App\Modules\User\Interfaces\SavedAddressRepositoryInterface;
use App\Modules\User\Interfaces\SavedAddressServiceInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Repositories\MerchantProfileRepository;
use App\Modules\User\Repositories\ProfileRepository;
use App\Modules\User\Repositories\SavedAddressRepository;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Repositories\DriverProfileRepository;
use App\Modules\User\Repositories\DriverGroupRepository;
use App\Modules\User\Interfaces\DriverGroupRepositoryInterface;
use App\Modules\User\Services\AdminDriverService;
use App\Modules\User\Services\AdminUserService;
use App\Modules\User\Services\ProfileService;
use App\Modules\User\Services\SavedAddressService;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use App\Modules\User\Events\UserStatusUpdated;
use App\Modules\User\Events\DriverApplicationApproved;
use App\Modules\User\Events\DriverApplicationRejected;
use App\Modules\User\Listeners\NotifyRealtimeOnUserStatusUpdated;
use App\Modules\User\Listeners\NotifyRealtimeOnDriverApplicationApproved;
use App\Modules\User\Listeners\NotifyRealtimeOnDriverApplicationRejected;

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
        $this->app->singleton(AdminUserServiceInterface::class, AdminUserService::class);
        $this->app->singleton(AdminDriverServiceInterface::class, AdminDriverService::class);

        // ── Repositories (singleton) ────────────────────────
        $this->app->singleton(SavedAddressRepositoryInterface::class, SavedAddressRepository::class);
        $this->app->singleton(ProfileRepositoryInterface::class, ProfileRepository::class);
        $this->app->singleton(UserRepositoryInterface::class, UserRepository::class);
        $this->app->singleton(DriverProfileRepositoryInterface::class, DriverProfileRepository::class);
        $this->app->singleton(DriverGroupRepositoryInterface::class, DriverGroupRepository::class);
        $this->app->singleton(MerchantProfileRepositoryInterface::class, MerchantProfileRepository::class);
    }

    public function boot(): void
    {
        // Đăng ký middleware kiểm tra trạng thái tài khoản
        /** @var Router $router */
        $router = $this->app->make('router');
        $router->aliasMiddleware('check.account.status', CheckAccountStatus::class);

        // Đăng ký Event Listeners cho Real-time
        Event::listen(
            UserStatusUpdated::class,
            NotifyRealtimeOnUserStatusUpdated::class
        );

        Event::listen(
            DriverApplicationApproved::class,
            NotifyRealtimeOnDriverApplicationApproved::class
        );

        Event::listen(
            DriverApplicationRejected::class,
            NotifyRealtimeOnDriverApplicationRejected::class
        );

        parent::boot();
    }
}
