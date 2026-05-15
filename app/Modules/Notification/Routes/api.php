<?php

declare(strict_types=1);

use App\Modules\Notification\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notification Module Routes
|--------------------------------------------------------------------------
|
| UC-126: View Notifications
|
*/

Route::prefix('v1/notifications')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('notification.index');
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('notification.read');
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('notification.read_all');
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('notification.destroy');
    Route::post('/update-token', [NotificationController::class, 'updateDeviceToken'])->name('notification.update_token');
});
