<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Deployment;
use App\Models\Site;
use App\Models\SyntheticRun;
use App\Services\PerformanceHub\GetSiteMetricsAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SiteDetailPageController extends Controller
{
    public function __invoke(Request $request, GetSiteMetricsAction $getSiteMetrics, string $siteId): View
    {
        $filters = [
            'from' => $request->string('from')->toString() ?: now()->subDays(13)->toDateString(),
            'to' => $request->string('to')->toString() ?: now()->toDateString(),
            'metric' => $request->string('metric')->toString() ?: null,
            'deviceClass' => $request->string('deviceClass')->toString() ?: 'mobile',
            'pageGroupKey' => $request->string('pageGroupKey')->toString() ?: null,
        ];

        $payload = $getSiteMetrics($siteId, $filters);
        $site = $payload['site'];
        $metricSlices = collect($payload['metrics']);
        $deployments = Deployment::query()
            ->where('site_id', $site->id)
            ->orderByDesc('deployed_at')
            ->limit(8)
            ->get();

        $syntheticRuns = SyntheticRun::query()
            ->where('site_id', $site->id)
            ->orderByDesc('occurred_at')
            ->limit(6)
            ->get();

        return view('dashboard.site-detail', [
            'activeSiteId' => $site->id,
            'deployments' => $deployments,
            'filters' => $filters,
            'latestSyntheticRun' => $syntheticRuns->first(),
            'metricPanels' => $this->buildMetricPanels($metricSlices),
            'navSites' => $this->navigationSites(),
            'pageGroupPanels' => $this->buildPageGroupPanels($metricSlices),
            'site' => $site,
            'siteMetrics' => $metricSlices,
            'syntheticRuns' => $syntheticRuns,
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $metricSlices
     * @return list<array<string, mixed>>
     */
    private function buildMetricPanels(Collection $metricSlices): array
    {
        return $metricSlices
            ->groupBy('metricName')
            ->map(function (Collection $group): array {
                $sorted = $group->sortBy('date')->values();
                $latest = $sorted->last();
                $previous = $sorted->count() > 1 ? $sorted->slice(-2, 1)->first() : null;

                return [
                    'metricName' => strtoupper($latest['metricName']),
                    'metricKey' => $latest['metricName'],
                    'latestValue' => $latest['p75Value'],
                    'sampleCount' => $latest['sampleCount'],
                    'delta' => $previous === null ? null : round($latest['p75Value'] - $previous['p75Value'], 3),
                    'points' => $sorted->pluck('p75Value')->map(fn (mixed $value): float => (float) $value)->all(),
                    'latestDate' => $latest['date'],
                ];
            })
            ->sortBy('metricKey')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $metricSlices
     * @return list<array<string, mixed>>
     */
    private function buildPageGroupPanels(Collection $metricSlices): array
    {
        return $metricSlices
            ->groupBy('pageGroupKey')
            ->map(function (Collection $group): array {
                $latest = $group->sortBy('date')->last();

                return [
                    'pageGroupKey' => $latest['pageGroupKey'],
                    'metricName' => strtoupper($latest['metricName']),
                    'p75Value' => $latest['p75Value'],
                    'poorCount' => $latest['poorCount'],
                    'sampleCount' => $latest['sampleCount'],
                    'latestDate' => $latest['date'],
                ];
            })
            ->sortByDesc('p75Value')
            ->values()
            ->all();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Site>
     */
    private function navigationSites(): EloquentCollection
    {
        return Site::query()
            ->orderBy('name')
            ->get(['id', 'slug', 'name']);
    }
}
