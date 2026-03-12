<?php

namespace App\Services\PerformanceHub;

use App\Models\DailyMetricRollup;
use Illuminate\Support\Collection;

class GetOverviewAction
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{health: list<array<string, mixed>>, trends: list<array<string, mixed>>}
     */
    public function __invoke(array $filters): array
    {
        $rollups = DailyMetricRollup::query()
            ->with('site')
            ->whereBetween('metric_day', [$filters['from'], $filters['to']])
            ->when(
                $filters['environment'] ?? null,
                fn ($query, string $environment) => $query->where('environment', $environment)
            )
            ->orderBy('metric_day')
            ->get();

        return [
            'health' => $this->buildHealthSummaries($rollups),
            'trends' => $this->buildTrendSlices($rollups),
        ];
    }

    /**
     * @param  Collection<int, DailyMetricRollup>  $rollups
     * @return list<array<string, mixed>>
     */
    private function buildHealthSummaries(Collection $rollups): array
    {
        $thresholds = config('performance-hub.health_thresholds');

        return $rollups
            ->groupBy('site_id')
            ->map(function (Collection $siteRollups) use ($thresholds): array {
                $latestDay = $siteRollups->max(fn (DailyMetricRollup $rollup) => $rollup->metric_day->toDateString());
                $latestRollups = $siteRollups
                    ->filter(fn (DailyMetricRollup $rollup): bool => $rollup->metric_day->toDateString() === $latestDay)
                    ->filter(fn (DailyMetricRollup $rollup): bool => array_key_exists($rollup->metric_name, $thresholds))
                    ->values();

                $sampleCount = max($latestRollups->sum('sample_count'), 1);
                $site = $siteRollups->firstOrFail()->site;

                return [
                    'siteId' => $site->id,
                    'siteSlug' => $site->slug,
                    'siteName' => $site->name,
                    'failingMetrics' => $latestRollups
                        ->filter(fn (DailyMetricRollup $rollup): bool => (float) $rollup->p75_value > (float) $thresholds[$rollup->metric_name])
                        ->pluck('metric_name')
                        ->unique()
                        ->values()
                        ->all(),
                    'poorEventShare' => round($latestRollups->sum('poor_count') / $sampleCount, 4),
                ];
            })
            ->sortByDesc('poorEventShare')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, DailyMetricRollup>  $rollups
     * @return list<array<string, mixed>>
     */
    private function buildTrendSlices(Collection $rollups): array
    {
        return $rollups
            ->groupBy(function (DailyMetricRollup $rollup): string {
                return implode('|', [
                    $rollup->metric_day->toDateString(),
                    $rollup->metric_name,
                    $rollup->device_class,
                ]);
            })
            ->map(function (Collection $group): array {
                $first = $group->firstOrFail();
                $sampleCount = max($group->sum('sample_count'), 1);

                return [
                    'date' => $first->metric_day->toDateString(),
                    'metricName' => $first->metric_name,
                    'deviceClass' => $first->device_class,
                    'pageGroupKey' => null,
                    'p75Value' => $this->weightedAverage($group, 'p75_value'),
                    'p50Value' => $this->weightedAverage($group, 'p50_value'),
                    'sampleCount' => $group->sum('sample_count'),
                    'poorCount' => $group->sum('poor_count'),
                ];
            })
            ->sortBy(['date', 'metricName', 'deviceClass'])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, DailyMetricRollup>  $rollups
     */
    private function weightedAverage(Collection $rollups, string $column): float
    {
        $sampleCount = max($rollups->sum('sample_count'), 1);

        $weightedTotal = $rollups->sum(function (DailyMetricRollup $rollup) use ($column): float {
            return ((float) $rollup->{$column}) * $rollup->sample_count;
        });

        return round($weightedTotal / $sampleCount, 3);
    }
}
