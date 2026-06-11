<?php

use App\Http\Controllers\Institution\ManagerController;
use App\Http\Controllers\Institution\StaffController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('institution')->name('institution.')->group(function () {
    // Manager
    Route::get('/queue', [ManagerController::class, 'queue'])->name('queue');
    Route::get('/requests/{request}', [ManagerController::class, 'show'])->name('show');
    Route::post('/requests/{request}/assign-self', [ManagerController::class, 'assignSelf'])->name('assign-self');
    Route::post('/requests/{request}/assign', [ManagerController::class, 'assign'])->name('assign');
    Route::post('/requests/{request}/approve-response', [ManagerController::class, 'approveResponse'])->name('approve-response');
    Route::post('/requests/{request}/reject-response', [ManagerController::class, 'rejectResponse'])->name('reject-response');
    Route::post('/requests/{request}/release-response', [ManagerController::class, 'releaseResponse'])->name('release-response');
    Route::post('/requests/{request}/messages', [MessageController::class, 'store'])->name('messages.store');

    // Staff
    Route::get('/assigned', [StaffController::class, 'index'])->name('assigned');
    Route::get('/assigned/{request}', [StaffController::class, 'show'])->name('assigned.show');
    Route::post('/assigned/{request}/response', [StaffController::class, 'storeResponse'])->name('response.store');
    Route::post('/assigned/{request}/response/submit', [StaffController::class, 'submitResponse'])->name('response.submit');
    Route::post('/assigned/{request}/messages', [MessageController::class, 'store'])->name('assigned.messages.store');
});
