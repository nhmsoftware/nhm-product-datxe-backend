<?php

use App\Modules\Auth\Providers\UserServiceProvider;
use App\Providers\AppServiceProvider;

return [
    // Swagger Provider
    L5Swagger\L5SwaggerServiceProvider::class,
    AppServiceProvider::class,
    UserServiceProvider::class,
    \Laravel\Socialite\SocialiteServiceProvider::class,

];
