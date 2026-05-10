<?php

use App\Modules\Auth\Providers\AuthServiceProvider;
use App\Modules\Driver\Providers\DriverServiceProvider;
use App\Modules\Finance\Providers\FinanceServiceProvider;
use App\Modules\Homepage\Providers\HomepageServiceProvider;
use App\Modules\Pricing\Providers\PricingServiceProvider;
use App\Modules\Ride\Providers\RideServiceProvider;
use App\Modules\User\Providers\UserServiceProvider;
use App\Providers\AppServiceProvider;
use Laravel\Socialite\SocialiteServiceProvider;

return [
    // Swagger Provider
    L5Swagger\L5SwaggerServiceProvider::class,
    AppServiceProvider::class,
    AuthServiceProvider::class,
    UserServiceProvider::class,
    HomepageServiceProvider::class,
    RideServiceProvider::class,
    FinanceServiceProvider::class,
    PricingServiceProvider::class,
    SocialiteServiceProvider::class,
    DriverServiceProvider::class,
    App\Modules\Operation\Providers\OperationServiceProvider::class,
    App\Modules\Dashboard\Providers\DashboardServiceProvider::class,
    App\Modules\RiskManagement\Providers\RiskManagementServiceProvider::class,
    App\Modules\Merchant\Providers\MerchantServiceProvider::class,
    App\Modules\Food\Providers\FoodServiceProvider::class,
    App\Modules\Order\Providers\OrderServiceProvider::class,
];
