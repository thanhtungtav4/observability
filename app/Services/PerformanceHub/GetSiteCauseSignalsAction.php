<?php

namespace App\Services\PerformanceHub;

use App\Models\Site;
use App\Models\SyntheticRun;
use App\Models\VitalsEvent;
use App\Models\VitalsEventJavascriptError;
use App\Models\VitalsEventLongTask;
use App\Models\VitalsEventResource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetSiteCauseSignalsAction
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     site: Site,
     *     signals: array{
     *         summary: array<string, int>,
     *         phaseBreakdown: list<array<string, mixed>>,
     *         layoutShiftHotspots: list<array<string, mixed>>,
     *         resourceHotspots: list<array<string, mixed>>,
     *         interactionHotspots: list<array<string, mixed>>,
     *         errorHotspots: list<array<string, mixed>>,
     *         labOpportunities: list<array<string, mixed>>
     *     }
     * }
     */
    public function __invoke(string $siteId, array $filters): array
    {
        $site = Site::query()->findOrFail($siteId);

        $events = $this->filteredVitalsEvents($site->id, $filters)
            ->get(['id', 'metric_name', 'metric_value', 'page_group_key', 'rating', 'attribution']);

        $resources = $this->filteredResources($site->id, $filters)
            ->get([
                'resource_url',
                'resource_host',
                'resource_path',
                'resource_type',
                'duration_ms',
                'transfer_size',
                'render_blocking',
                'is_lcp_candidate',
            ]);

        $longTasks = $this->filteredLongTasks($site->id, $filters)
            ->get([
                'name',
                'script_url',
                'script_host',
                'invoker_type',
                'container_selector',
                'duration_ms',
                'blocking_duration_ms',
            ]);

        $javascriptErrors = $this->filteredJavascriptErrors($site->id, $filters)
            ->get([
                'fingerprint',
                'name',
                'message',
                'source_url',
                'source_host',
                'handled',
            ]);

        $syntheticRuns = $this->filteredSyntheticRuns($site->id, $filters)
            ->get(['opportunities']);

        return [
            'site' => $site,
            'signals' => [
                'summary' => [
                    'eventCount' => $events->count(),
                    'resourceCount' => $resources->count(),
                    'longTaskCount' => $longTasks->count(),
                    'errorCount' => $javascriptErrors->count(),
                    'syntheticOpportunityCount' => $syntheticRuns
                        ->flatMap(fn (SyntheticRun $run): array => is_array($run->opportunities) ? $run->opportunities : [])
                        ->count(),
                ],
                'phaseBreakdown' => array_values(array_filter([
                    $this->buildPhasePanel(
                        $events->where('metric_name', 'lcp')->values(),
                        'lcp',
                        'Largest Contentful Paint',
                        [
                            'ttfb' => ['label' => 'TTFB', 'keys' => ['timeToFirstByte', 'ttfb']],
                            'resource_load_delay' => ['label' => 'Resource load delay', 'keys' => ['resourceLoadDelay']],
                            'resource_load_duration' => ['label' => 'Resource load duration', 'keys' => ['resourceLoadDuration']],
                            'render_delay' => ['label' => 'Render delay', 'keys' => ['elementRenderDelay', 'renderDelay']],
                        ],
                    ),
                    $this->buildPhasePanel(
                        $events->where('metric_name', 'inp')->values(),
                        'inp',
                        'Interaction to Next Paint',
                        [
                            'input_delay' => ['label' => 'Input delay', 'keys' => ['inputDelay']],
                            'processing_duration' => ['label' => 'Processing duration', 'keys' => ['processingDuration']],
                            'presentation_delay' => ['label' => 'Presentation delay', 'keys' => ['presentationDelay']],
                        ],
                    ),
                ])),
                'layoutShiftHotspots' => $this->buildLayoutShiftHotspots($events),
                'resourceHotspots' => $this->buildResourceHotspots($resources),
                'interactionHotspots' => $this->buildInteractionHotspots($longTasks),
                'errorHotspots' => $this->buildErrorHotspots($javascriptErrors),
                'labOpportunities' => $this->buildLabOpportunities($syntheticRuns),
            ],
        ];
    }

    /**
     * @param  Collection<int, VitalsEvent>  $events
     * @param  array<string, array{label: string, keys: list<string>}>  $catalog
     * @return array<string, mixed>|null
     */
    private function buildPhasePanel(Collection $events, string $metricName, string $title, array $catalog): ?array
    {
        if ($events->isEmpty()) {
            return null;
        }

        $samples = $events
            ->map(function (VitalsEvent $event) use ($catalog): ?array {
                $phaseValues = collect($catalog)
                    ->mapWithKeys(function (array $definition, string $phaseKey) use ($event): array {
                        return [
                            $phaseKey => $this->numericAttributionValue($event->attribution ?? [], $definition['keys']),
                        ];
                    })
                    ->filter(fn (?float $value): bool => $value !== null);

                if ($phaseValues->isEmpty()) {
                    return null;
                }

                $dominantPhase = $phaseValues->sortDesc()->keys()->first();

                if (! is_string($dominantPhase)) {
                    return null;
                }

                return [
                    'phaseKey' => $dominantPhase,
                    'phaseLabel' => $catalog[$dominantPhase]['label'],
                    'value' => (float) $phaseValues->get($dominantPhase),
                    'metricValue' => (float) $event->metric_value,
                ];
            })
            ->filter()
            ->values();

        if ($samples->isEmpty()) {
            return null;
        }

        $totalSamples = $samples->count();

        return [
            'metricName' => $metricName,
            'title' => $title,
            'rows' => $samples
                ->groupBy('phaseKey')
                ->map(function (Collection $group): array {
                    $first = $group->first();

                    return [
                        'phase' => $first['phaseLabel'],
                        'count' => $group->count(),
                        'avgContribution' => round($group->avg('value'), 1),
                        'avgMetricValue' => round($group->avg('metricValue'), 1),
                    ];
                })
                ->sortByDesc(fn (array $row): float => ($row['count'] * 100000) + $row['avgContribution'])
                ->take(4)
                ->values()
                ->map(function (array $row) use ($totalSamples): array {
                    $row['share'] = round(($row['count'] / max(1, $totalSamples)) * 100, 1);

                    return $row;
                })
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, VitalsEvent>  $events
     * @return list<array<string, mixed>>
     */
    private function buildLayoutShiftHotspots(Collection $events): array
    {
        $samples = $events
            ->where('metric_name', 'cls')
            ->map(function (VitalsEvent $event): ?array {
                $target = $this->stringAttributionValue(
                    $event->attribution ?? [],
                    ['largestShiftTarget', 'shiftTarget', 'largestShiftSource', 'shiftSource', 'culpritSelector'],
                );

                if ($target === null) {
                    return null;
                }

                return [
                    'target' => $target,
                    'metricValue' => (float) $event->metric_value,
                ];
            })
            ->filter()
            ->values();

        $totalSamples = $samples->count();

        return $samples
            ->groupBy('target')
            ->map(function (Collection $group, string $target): array {
                return [
                    'target' => $target,
                    'count' => $group->count(),
                    'avgScore' => round($group->avg('metricValue'), 3),
                ];
            })
            ->sortByDesc(fn (array $row): float => ($row['count'] * 1000) + $row['avgScore'])
            ->take(4)
            ->values()
            ->map(function (array $row) use ($totalSamples): array {
                $row['share'] = round(($row['count'] / max(1, $totalSamples)) * 100, 1);

                return $row;
            })
            ->all();
    }

    /**
     * @param  Collection<int, VitalsEventResource>  $resources
     * @return list<array<string, mixed>>
     */
    private function buildResourceHotspots(Collection $resources): array
    {
        return $resources
            ->groupBy(function (VitalsEventResource $resource): string {
                return implode('|', [
                    $resource->resource_host ?? 'unknown-host',
                    $resource->resource_path ?? $resource->resource_url,
                    $resource->resource_type ?? 'resource',
                ]);
            })
            ->map(function (Collection $group): array {
                /** @var VitalsEventResource $first */
                $first = $group->first();

                return [
                    'label' => ($first->resource_host ?? 'Unknown host').($first->resource_path ?? ''),
                    'host' => $first->resource_host ?? 'Unknown host',
                    'resourceType' => $first->resource_type ?? 'resource',
                    'count' => $group->count(),
                    'avgDurationMs' => round($group->avg(fn (VitalsEventResource $resource): float => (float) ($resource->duration_ms ?? 0)), 1),
                    'avgTransferSize' => (int) round($group->avg(fn (VitalsEventResource $resource): float => (float) ($resource->transfer_size ?? 0))),
                    'renderBlockingCount' => $group->where('render_blocking', true)->count(),
                    'lcpCandidateCount' => $group->where('is_lcp_candidate', true)->count(),
                    'sampleUrl' => $first->resource_url,
                ];
            })
            ->sortByDesc(fn (array $row): float => ($row['count'] * 100000) + $row['avgDurationMs'])
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, VitalsEventLongTask>  $longTasks
     * @return list<array<string, mixed>>
     */
    private function buildInteractionHotspots(Collection $longTasks): array
    {
        return $longTasks
            ->groupBy(function (VitalsEventLongTask $longTask): string {
                return implode('|', [
                    $longTask->script_host ?? 'unknown-host',
                    $longTask->container_selector ?? 'document',
                    $longTask->invoker_type ?? 'event',
                ]);
            })
            ->map(function (Collection $group): array {
                /** @var VitalsEventLongTask $first */
                $first = $group->first();

                return [
                    'scriptHost' => $first->script_host ?? 'Unknown host',
                    'containerSelector' => $first->container_selector ?? 'document',
                    'invokerType' => $first->invoker_type ?? 'event',
                    'count' => $group->count(),
                    'avgDurationMs' => round($group->avg(fn (VitalsEventLongTask $longTask): float => (float) $longTask->duration_ms), 1),
                    'avgBlockingDurationMs' => round($group->avg(fn (VitalsEventLongTask $longTask): float => (float) ($longTask->blocking_duration_ms ?? 0)), 1),
                    'sampleScriptUrl' => $first->script_url,
                ];
            })
            ->sortByDesc(fn (array $row): float => ($row['count'] * 100000) + $row['avgBlockingDurationMs'])
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, VitalsEventJavascriptError>  $javascriptErrors
     * @return list<array<string, mixed>>
     */
    private function buildErrorHotspots(Collection $javascriptErrors): array
    {
        return $javascriptErrors
            ->groupBy('fingerprint')
            ->map(function (Collection $group): array {
                /** @var VitalsEventJavascriptError $first */
                $first = $group->first();

                return [
                    'name' => $first->name ?? 'JavaScriptError',
                    'message' => $first->message,
                    'sourceHost' => $first->source_host ?? 'Unknown host',
                    'count' => $group->count(),
                    'handledRate' => round(($group->where('handled', true)->count() / max(1, $group->count())) * 100, 1),
                    'sampleSourceUrl' => $first->source_url,
                ];
            })
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, SyntheticRun>  $syntheticRuns
     * @return list<array<string, mixed>>
     */
    private function buildLabOpportunities(Collection $syntheticRuns): array
    {
        return $syntheticRuns
            ->flatMap(function (SyntheticRun $run): array {
                return collect($run->opportunities ?? [])
                    ->filter(fn (mixed $opportunity): bool => is_array($opportunity) && is_string($opportunity['title'] ?? null))
                    ->values()
                    ->all();
            })
            ->groupBy('title')
            ->map(fn (Collection $group, string $title): array => [
                'title' => $title,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->take(4)
            ->values()
            ->all();
    }

    private function filteredVitalsEvents(string $siteId, array $filters): Builder
    {
        return $this->applyVitalsEventFilters(VitalsEvent::query(), $siteId, $filters);
    }

    private function filteredResources(string $siteId, array $filters): Builder
    {
        return VitalsEventResource::query()
            ->whereHas('vitalsEvent', function (Builder $query) use ($siteId, $filters): void {
                $this->applyVitalsEventFilters($query, $siteId, $filters);
            });
    }

    private function filteredLongTasks(string $siteId, array $filters): Builder
    {
        return VitalsEventLongTask::query()
            ->whereHas('vitalsEvent', function (Builder $query) use ($siteId, $filters): void {
                $this->applyVitalsEventFilters($query, $siteId, $filters);
            });
    }

    private function filteredJavascriptErrors(string $siteId, array $filters): Builder
    {
        return VitalsEventJavascriptError::query()
            ->whereHas('vitalsEvent', function (Builder $query) use ($siteId, $filters): void {
                $this->applyVitalsEventFilters($query, $siteId, $filters);
            });
    }

    private function filteredSyntheticRuns(string $siteId, array $filters): Builder
    {
        $from = CarbonImmutable::parse($filters['from'])->startOfDay();
        $to = CarbonImmutable::parse($filters['to'])->endOfDay();

        return SyntheticRun::query()
            ->where('site_id', $siteId)
            ->whereBetween('occurred_at', [$from, $to])
            ->when(
                $filters['environment'] ?? null,
                fn (Builder $query, string $environment): Builder => $query->where('environment', $environment)
            )
            ->when(
                $filters['pageGroupKey'] ?? null,
                fn (Builder $query, string $pageGroupKey): Builder => $query->where('page_group_key', $pageGroupKey)
            );
    }

    private function applyVitalsEventFilters(Builder $query, string $siteId, array $filters): Builder
    {
        $from = CarbonImmutable::parse($filters['from'])->startOfDay();
        $to = CarbonImmutable::parse($filters['to'])->endOfDay();

        return $query
            ->where('site_id', $siteId)
            ->whereBetween('occurred_at', [$from, $to])
            ->when(
                $filters['environment'] ?? null,
                fn (Builder $builder, string $environment): Builder => $builder->where('environment', $environment)
            )
            ->when(
                $filters['metric'] ?? null,
                fn (Builder $builder, string $metric): Builder => $builder->where('metric_name', $metric)
            )
            ->when(
                $filters['deviceClass'] ?? null,
                fn (Builder $builder, string $deviceClass): Builder => $builder->where('device_class', $deviceClass)
            )
            ->when(
                $filters['pageGroupKey'] ?? null,
                fn (Builder $builder, string $pageGroupKey): Builder => $builder->where('page_group_key', $pageGroupKey)
            );
    }

    /**
     * @param  array<string, mixed>  $attribution
     * @param  list<string>  $keys
     */
    private function numericAttributionValue(array $attribution, array $keys): ?float
    {
        foreach ($keys as $key) {
            $value = data_get($attribution, $key);

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $attribution
     * @param  list<string>  $keys
     */
    private function stringAttributionValue(array $attribution, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($attribution, $key);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
