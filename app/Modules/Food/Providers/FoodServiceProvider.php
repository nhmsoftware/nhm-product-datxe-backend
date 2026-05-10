<?php

declare(strict_types=1);

namespace App\Modules\Food\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Food\Interfaces\FoodOrderRepositoryInterface;
use App\Modules\Food\Interfaces\FoodOrderServiceInterface;
use App\Modules\Food\Repositories\FoodOrderRepository;
use App\Modules\Food\Services\FoodOrderService;

final class FoodServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleName(): string
    {
        return 'Food';
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(\App\Modules\Food\Interfaces\FoodOrderRepositoryInterface::class, \App\Modules\Food\Repositories\FoodOrderRepository::class);
        $this->app->singleton(\App\Modules\Food\Interfaces\FoodOrderServiceInterface::class, \App\Modules\Food\Services\FoodOrderService::class);
        
        $this->app->singleton(\App\Modules\Food\Interfaces\FoodRatingRepositoryInterface::class, \App\Modules\Food\Repositories\FoodRatingRepository::class);
        $this->app->singleton(\App\Modules\Food\Interfaces\FoodRatingServiceInterface::class, \App\Modules\Food\Services\FoodRatingService::class);
    }

    public function boot(): void
    {
        // Register event mappings
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Food\Events\FoodOrderCreated::class,
            \App\Modules\Food\Listeners\NotifyMerchantOnFoodOrderCreated::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Food\Events\FoodOrderRated::class,
            \App\Modules\Food\Listeners\UpdateMerchantRatingStats::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Food\Events\FoodOrderRated::class,
            \App\Modules\Food\Listeners\UpdateMenuItemRatingStats::class
        );

        parent::boot();
    }
}
