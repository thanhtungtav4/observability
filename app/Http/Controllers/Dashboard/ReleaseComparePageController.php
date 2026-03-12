<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Deployment;
use App\Models\Site;
use App\Services\PerformanceHub\CompareDeploymentsAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReleaseComparePageController extends Controller
{
    public function __invoke(Request $request, CompareDeploymentsAction $compareDeployments, string $siteId): View
    {
        $site = Site::query()->findOrFail($siteId);
        $deployments = Deployment::query()
            ->where('site_id', $site->id)
            ->orderByDesc('deployed_at')
            ->get();

        $filters = [
            'currentDeploymentId' => $request->string('currentDeploymentId')->toString() ?: $deployments->first()?->id,
            'baselineDeploymentId' => $request->string('baselineDeploymentId')->toString() ?: null,
            'deviceClass' => $request->string('deviceClass')->toString() ?: 'mobile',
        ];

        $comparison = null;
        $comparisonError = null;

        if ($filters['currentDeploymentId'] !== null && $deployments->count() > 1) {
            try {
                $comparison = $compareDeployments($site->id, array_filter($filters));
            } catch (ValidationException $exception) {
                $comparisonError = $exception->getMessage();
            }
        }

        return view('dashboard.release-compare', [
            'activeSiteId' => $site->id,
            'comparison' => $comparison,
            'comparisonError' => $comparisonError,
            'deployments' => $deployments,
            'filters' => $filters,
            'navSites' => $this->navigationSites(),
            'site' => $site,
            'summary' => $this->buildSummary($comparison),
        ]);
    }

    /**
     * @param  array{currentDeployment: Deployment, baselineDeployment: Deployment, metrics: list<array<string, mixed>>}|null  $comparison
     * @return array<string, mixed>|null
     */
    private function buildSummary(?array $comparison): ?array
    {
        if ($comparison === null) {
            return null;
        }

        $metrics = collect($comparison['metrics']);
        $largestRegression = $metrics
            ->filter(fn (array $metric): bool => $metric['delta'] > 0)
            ->sortByDesc('delta')
            ->first();
        $largestImprovement = $metrics
            ->filter(fn (array $metric): bool => $metric['delta'] < 0)
            ->sortBy('delta')
            ->first();

        return [
            'regressions' => $metrics->filter(fn (array $metric): bool => $metric['delta'] > 0)->count(),
            'improvements' => $metrics->filter(fn (array $metric): bool => $metric['delta'] < 0)->count(),
            'largestRegression' => $largestRegression,
            'largestImprovement' => $largestImprovement,
        ];
    }

    /**
     * @return Collection<int, Site>
     */
    private function navigationSites(): EloquentCollection
    {
        return Site::query()
            ->orderBy('name')
            ->get(['id', 'slug', 'name']);
    }
}
