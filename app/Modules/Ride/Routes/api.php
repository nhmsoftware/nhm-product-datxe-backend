<?php

declare(strict_types=1);

namespace App\Modules\Ride\Routes;

use App\Modules\Ride\Http\Controllers\RideController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/ride')->middleware('auth:sanctum')->group(function () {
    Route::post('draft', [RideController::class, 'createDraft'])->name('draft');
});
