<?php

use Illuminate\Support\Facades\Route;
use App\Modules\User\Http\Controllers\AuthController;

Route::group([
    'prefix' => 'user',
    'middleware' => 'api',
], function () {
    Route::post('/login', [AuthController::class, 'login']);
});
