<?php

use App\Modules\Auth\Providers\AuthServiceProvider;
use App\Modules\User\Providers\UserServiceProvider;
use App\Providers\AppServiceProvider;
use Laravel\Socialite\SocialiteServiceProvider;

return [
    // Swagger Provider
    L5Swagger\L5SwaggerServiceProvider::class,
    AppServiceProvider::class,
    AuthServiceProvider::class,
    UserServiceProvider::class,
    \App\Modules\Homepage\Providers\HomepageServiceProvider::class,
    App\Modules\Ride\Providers\RideServiceProvider::class,
    SocialiteServiceProvider::class,

];
