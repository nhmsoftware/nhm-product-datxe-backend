<?php

use App\Modules\Auth\Providers\AuthServiceProvider;
use App\Providers\AppServiceProvider;
use Laravel\Socialite\SocialiteServiceProvider;

return [
    // Swagger Provider
    L5Swagger\L5SwaggerServiceProvider::class,
    AppServiceProvider::class,
    AuthServiceProvider::class,
    SocialiteServiceProvider::class,

];
