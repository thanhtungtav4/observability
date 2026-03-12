<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Dashboard\OverviewPageController;
use App\Http\Controllers\Dashboard\ReleaseComparePageController;
use App\Http\Controllers\Dashboard\SiteDetailPageController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');
});

Route::middleware(['auth', 'performance-hub.admin'])->group(function (): void {
    Route::get('/', OverviewPageController::class)
        ->name('dashboard.overview');

    Route::get('/sites/{siteId}', SiteDetailPageController::class)
        ->whereUuid('siteId')
        ->name('dashboard.sites.show');

    Route::get('/sites/{siteId}/compare', ReleaseComparePageController::class)
        ->whereUuid('siteId')
        ->name('dashboard.sites.compare');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
