<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SecurityLogController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('my/security', [SecurityLogController::class, 'index'])->name('security-log');
});

require __DIR__.'/settings.php';
require __DIR__.'/requester.php';
require __DIR__.'/amlakas.php';
require __DIR__.'/institution.php';
