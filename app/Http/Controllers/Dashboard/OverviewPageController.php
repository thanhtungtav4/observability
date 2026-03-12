<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DailyMetricRollup;
use App\Models\Site;
use App\Models\SyntheticRun;
use App\Models\VitalsEvent;
use App\Services\PerformanceHub\GetOverviewAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OverviewPageController extends Controller
{
    public function __invoke(Request $request, GetOverviewAction $getOverview): View
    {
        $filters = [
            'from' => $request->string('from')->toString() ?: now()->subDays(6)->toDateString(),
            'to' => $request->string('to')->toString() ?: now()->toDateString(),
            'environment' => $request->string('environment')->toString() ?: 'production',
        ];

        $overview = $getOverview($filters);
        $windowStart = $filters['from'].' 00:00:00';
        $windowEnd = $filters['to'].' 23:59:59';

        return view('dashboard.overview', [
            'activeSiteId' => null,
            'filters' => $filters,
            'healthCards' => $overview['health'],
            'metricCards' => $this->buildMetricCards($overview['trends']),
            'navSites' => $this->navigationSites(),
            'needsDemoSeed' => VitalsEvent::query()->doesntExist() && SyntheticRun::query()->doesntExist(),
            'needsRefresh' => VitalsEvent::query()->exists() && DailyMetricRollup::query()->doesntExist(),
            'stats' => [
                'siteCount' => Site::query()->count(),
                'alertCount' => collect($overview['health'])
                    ->filter(fn (array $site): bool => count($site['failingMetrics']) > 0)
                    ->count(),
                'eventCount' => VitalsEvent::query()
                    ->where('environment', $filters['environment'])
                    ->whereBetween('occurred_at', [$windowStart, $windowEnd])
                    ->count(),
                'syntheticCount' => SyntheticRun::query()
                    ->where('environment', $filters['environment'])
                    ->whereBetween('occurred_at', [$windowStart, $windowEnd])
                    ->count(),
            ],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $trends
     * @return list<array<string, mixed>>
     */
    private function buildMetricCards(array $trends): array
    {
        return collect($trends)
            ->groupBy(fn (array $trend): string => $trend['metricName'].'|'.$trend['deviceClass'])
            ->map(function (Collection $group): array {
                $sorted = $group->sortBy('date')->values();
                $latest = $sorted->last();
                $previous = $sorted->count() > 1 ? $sorted->slice(-2, 1)->first() : null;

                return [
                    'metricName' => strtoupper($latest['metricName']),
                    'metricKey' => $latest['metricName'],
                    'deviceClass' => $latest['deviceClass'],
                    'latestValue' => $latest['p75Value'],
                    'sampleCount' => $latest['sampleCount'],
                    'delta' => $previous === null ? null : round($latest['p75Value'] - $previous['p75Value'], 3),
                    'points' => $sorted->pluck('p75Value')->map(fn (mixed $value): float => (float) $value)->all(),
                    'latestDate' => $latest['date'],
                ];
            })
            ->sortByDesc('latestValue')
            ->values()
            ->all();
    }

    /**
     * @return EloquentCollection<int, Site>
     */
    private function navigationSites(): EloquentCollection
    {
        return Site::query()
            ->orderBy('name')
            ->get(['id', 'slug', 'name']);
    }
}
