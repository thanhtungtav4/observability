<?php

namespace App\Services\PerformanceHub;

use App\Models\DailyMetricRollup;
use App\Models\Site;

class GetSiteMetricsAction
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{site: Site, metrics: list<array<string, mixed>>}
     */
    public function __invoke(string $siteId, array $filters): array
    {
        $site = Site::query()->findOrFail($siteId);

        $metrics = DailyMetricRollup::query()
            ->where('site_id', $site->id)
            ->whereBetween('metric_day', [$filters['from'], $filters['to']])
            ->when(
                $filters['environment'] ?? null,
                fn ($query, string $environment) => $query->where('environment', $environment)
            )
            ->when(
                $filters['metric'] ?? null,
                fn ($query, string $metric) => $query->where('metric_name', $metric)
            )
            ->when(
                $filters['deviceClass'] ?? null,
                fn ($query, string $deviceClass) => $query->where('device_class', $deviceClass)
            )
            ->when(
                $filters['pageGroupKey'] ?? null,
                fn ($query, string $pageGroupKey) => $query->where('page_group_key', $pageGroupKey)
            )
            ->orderBy('metric_day')
            ->orderBy('metric_name')
            ->get()
            ->map(fn (DailyMetricRollup $rollup): array => [
                'date' => $rollup->metric_day->toDateString(),
                'environment' => $rollup->environment,
                'metricName' => $rollup->metric_name,
                'deviceClass' => $rollup->device_class,
                'pageGroupKey' => $rollup->page_group_key,
                'p75Value' => (float) $rollup->p75_value,
                'p50Value' => (float) $rollup->p50_value,
                'sampleCount' => $rollup->sample_count,
                'poorCount' => $rollup->poor_count,
            ])
            ->values()
            ->all();

        return [
            'site' => $site,
            'metrics' => $metrics,
        ];
    }
}
