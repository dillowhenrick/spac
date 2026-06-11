<?php

use App\Http\Controllers\AmlakasVerifier\SystemAuditController;
use App\Http\Controllers\AmlakasVerifier\VerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('verification')->name('verification.')->group(function () {
    Route::get('/', [VerificationController::class, 'index'])->name('index');
    Route::get('/audit', [SystemAuditController::class, 'index'])->name('audit');
    Route::get('/audit/{request}', [SystemAuditController::class, 'request'])->name('audit.request');
    Route::get('/{request}', [VerificationController::class, 'show'])->name('show');
    Route::post('/{request}/approve', [VerificationController::class, 'approve'])->name('approve');
    Route::post('/{request}/reject', [VerificationController::class, 'reject'])->name('reject');
    Route::post('/{request}/route', [VerificationController::class, 'route'])->name('route');
});
