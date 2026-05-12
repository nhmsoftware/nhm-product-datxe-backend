<?php

declare(strict_types=1);

use App\Modules\Chauffeur\Http\Controllers\ChauffeurController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/chauffeur')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/book', [ChauffeurController::class, 'book']);
    });
});
