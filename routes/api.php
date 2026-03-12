<?php

use App\Http\Controllers\Api\V1\CollectWebVitalsController;
use App\Http\Controllers\Api\V1\CompareDeploymentsController;
use App\Http\Controllers\Api\V1\GetOverviewController;
use App\Http\Controllers\Api\V1\GetSiteMetricsController;
use App\Http\Controllers\Api\V1\ListSitesController;
use App\Http\Controllers\Api\V1\StoreSyntheticRunController;
use App\Http\Controllers\Api\V1\UpsertDeploymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('collect/web-vitals', CollectWebVitalsController::class)
        ->name('api.v1.collect.web-vitals');

    Route::middleware('performance-hub.token')->group(function (): void {
        Route::post('deployments', UpsertDeploymentController::class)
            ->name('api.v1.deployments.store');

        Route::post('synthetic-runs', StoreSyntheticRunController::class)
            ->name('api.v1.synthetic-runs.store');

        Route::get('sites', ListSitesController::class)
            ->name('api.v1.sites.index');

        Route::get('dashboard/overview', GetOverviewController::class)
            ->name('api.v1.dashboard.overview');

        Route::get('sites/{siteId}/metrics', GetSiteMetricsController::class)
            ->name('api.v1.sites.metrics');

        Route::get('sites/{siteId}/deployments/compare', CompareDeploymentsController::class)
            ->name('api.v1.sites.deployments.compare');
    });
});
