<?php

use App\Http\Controllers\MessageController;
use App\Http\Controllers\Requester\RequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('requests')->name('requests.')->group(function () {
    Route::get('/', [RequestController::class, 'index'])->name('index');
    Route::get('/create', [RequestController::class, 'create'])->name('create');
    Route::post('/', [RequestController::class, 'store'])->name('store');
    Route::get('/{request}', [RequestController::class, 'show'])->name('show');
    Route::post('/{request}/submit', [RequestController::class, 'submit'])->name('submit');
    Route::post('/{request}/messages', [MessageController::class, 'store'])->name('messages.store');
});
