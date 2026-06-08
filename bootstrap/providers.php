<?php

use App\Modules\Auth\Providers\AuthServiceProvider;
use App\Modules\Chauffeur\Providers\ChauffeurServiceProvider;
use App\Modules\Complaint\Providers\ComplaintServiceProvider;
use App\Modules\Driver\Providers\DriverServiceProvider;
use App\Modules\Finance\Providers\FinanceServiceProvider;
use App\Modules\Homepage\Providers\HomepageServiceProvider;
use App\Modules\Pricing\Providers\PricingServiceProvider;
use App\Modules\Ride\Providers\RideServiceProvider;
use App\Modules\User\Providers\UserServiceProvider;
use App\Modules\Operation\Providers\OperationServiceProvider;
use App\Modules\Dashboard\Providers\DashboardServiceProvider;
use App\Modules\RiskManagement\Providers\RiskManagementServiceProvider;
use App\Modules\Merchant\Providers\MerchantServiceProvider;
use App\Modules\Food\Providers\FoodServiceProvider;
use App\Modules\Marketing\Providers\MarketingServiceProvider;
use App\Modules\Notification\Providers\NotificationServiceProvider;
use App\Modules\Order\Providers\OrderServiceProvider;
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
    OperationServiceProvider::class,
    DashboardServiceProvider::class,
    RiskManagementServiceProvider::class,
    MerchantServiceProvider::class,
    FoodServiceProvider::class,
    OrderServiceProvider::class,
    ChauffeurServiceProvider::class,
    ComplaintServiceProvider::class,
    NotificationServiceProvider::class,
    MarketingServiceProvider::class,
];
