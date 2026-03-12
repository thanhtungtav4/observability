<?php

use App\Http\Controllers\Dashboard\OverviewPageController;
use App\Http\Controllers\Dashboard\ReleaseComparePageController;
use App\Http\Controllers\Dashboard\SiteDetailPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', OverviewPageController::class)
    ->name('dashboard.overview');

Route::get('/sites/{siteId}', SiteDetailPageController::class)
    ->whereUuid('siteId')
    ->name('dashboard.sites.show');

Route::get('/sites/{siteId}/compare', ReleaseComparePageController::class)
    ->whereUuid('siteId')
    ->name('dashboard.sites.compare');
