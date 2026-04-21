<?php

use App\Modules\Communication\Http\Controllers\CommunicationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/communication')
    ->middleware(['auth:sanctum'])
    ->group(function () {

    // UC-14: Chat với tài xế/khách hàng
    Route::post('chat/{rideId}', [CommunicationController::class, 'sendMessage'])
        ->name('communication.chat.send');

    // UC-14: Lấy lịch sử chat
    Route::get('chat/{rideId}', [CommunicationController::class, 'getChatHistory'])
        ->name('communication.chat.history');

    // UC-14: Gọi điện cho tài xế/khách hàng
    Route::post('call/{rideId}', [CommunicationController::class, 'initiateCall'])
        ->name('communication.call.init');
});
