<?php

use App\Providers\AppServiceProvider;
use Modules\User\Infrastructure\Providers\UserServiceProvider;

return [
    // Swagger Provider
    L5Swagger\L5SwaggerServiceProvider::class,
    AppServiceProvider::class,
    UserServiceProvider::class,
];
