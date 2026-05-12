<?php

use App\Modules\Complaint\Http\Controllers\ComplaintController;
use Illuminate\Support\Facades\Route;

/**
 * Routes for Complaint Module
 * UC-108 Handle Complaints
 */
Route::prefix('v1/admin/complaints')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [ComplaintController::class, 'index'])->name('admin.complaints.index');
    Route::get('/{id}', [ComplaintController::class, 'show'])->name('admin.complaints.show');
    Route::post('/{id}/handle', [ComplaintController::class, 'handle'])->name('admin.complaints.handle');
});
