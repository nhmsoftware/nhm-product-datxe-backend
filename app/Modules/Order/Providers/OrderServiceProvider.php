<?php

declare(strict_types=1);

namespace App\Modules\Order\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Order\Interfaces\OrderRepositoryInterface;
use App\Modules\Order\Interfaces\OrderServiceInterface;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Order\Services\OrderService;

final class OrderServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleName(): string
    {
        return 'Order';
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->singleton(OrderServiceInterface::class, OrderService::class);
    }

    protected array $listen = [
        \App\Modules\Order\Events\FoodOrderStatusUpdated::class => [
            \App\Modules\Order\Listeners\NotifyRealtimeOnFoodOrderStatusUpdated::class,
        ],
        \App\Modules\Order\Events\FoodCancellationRequestHandled::class => [
            \App\Modules\Order\Listeners\NotifyRealtimeOnFoodCancellationHandled::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
