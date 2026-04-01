<?php

use App\Providers\AppServiceProvider;
use Modules\User\Infrastructure\Providers\UserServiceProvider;

return [
    AppServiceProvider::class,
    UserServiceProvider::class,
];
