<?php

use App\Modules\User\Providers\UserServiceProvider;
use App\Providers\AppServiceProvider;

return [
    // Swagger Provider
    L5Swagger\L5SwaggerServiceProvider::class,
    AppServiceProvider::class,
    UserServiceProvider::class,
    \Laravel\Socialite\SocialiteServiceProvider::class,

];
