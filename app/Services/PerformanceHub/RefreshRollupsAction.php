<?php

namespace App\Services\PerformanceHub;

use App\Models\DailyMetricRollup;
use App\Models\DeploymentMetricRollup;
use App\Models\VitalsEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RefreshRollupsAction
{
    /**
     * @return array{daily_rollups: int, deployment_rollups: int}
     */
    public function __invoke(): array
    {
        $events = VitalsEvent::query()
            ->orderBy('occurred_at')
            ->get();

        $dailyRollups = $this->buildDailyRollups($events);
        $deploymentRollups = $this->buildDeploymentRollups(
            $events->filter(fn (VitalsEvent $event): bool => $event->deployment_id !== null)->values()
        );

        DB::transaction(function () use ($dailyRollups, $deploymentRollups): void {
            DailyMetricRollup::query()->delete();
            DeploymentMetricRollup::query()->delete();

            foreach (array_chunk($dailyRollups, 500) as $chunk) {
                DailyMetricRollup::query()->insert($chunk);
            }

            foreach (array_chunk($deploymentRollups, 500) as $chunk) {
                DeploymentMetricRollup::query()->insert($chunk);
            }
        });

        return [
            'daily_rollups' => count($dailyRollups),
            'deployment_rollups' => count($deploymentRollups),
        ];
    }

    /**
     * @param  Collection<int, VitalsEvent>  $events
     * @return list<array<string, mixed>>
     */
    private function buildDailyRollups(Collection $events): array
    {
        return $events
            ->groupBy(function (VitalsEvent $event): string {
                return implode('|', [
                    $event->occurred_at->clone()->utc()->toDateString(),
                    $event->site_id,
                    $event->environment,
                    $event->page_group_key,
                    $event->deployment_id ?? 'null',
                    $event->build_id,
                    $event->metric_name,
                    $event->metric_unit,
                    $event->device_class,
                ]);
            })
            ->map(fn (Collection $group): array => $this->mapDailyRollup($group))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, VitalsEvent>  $events
     * @return list<array<string, mixed>>
     */
    private function buildDeploymentRollups(Collection $events): array
    {
        return $events
            ->groupBy(function (VitalsEvent $event): string {
                return implode('|', [
                    $event->deployment_id,
                    $event->site_id,
                    $event->environment,
                    $event->page_group_key,
                    $event->build_id,
                    $event->release_version ?? '',
                    $event->metric_name,
                    $event->metric_unit,
                    $event->device_class,
                ]);
            })
            ->map(fn (Collection $group): array => $this->mapDeploymentRollup($group))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, VitalsEvent>  $group
     * @return array<string, mixed>
     */
    private function mapDailyRollup(Collection $group): array
    {
        $first = $group->firstOrFail();
        $sampleCount = $group->count();
        $now = now();

        return [
            'metric_day' => $first->occurred_at->clone()->utc()->toDateString(),
            'site_id' => $first->site_id,
            'environment' => $first->environment,
            'page_group_key' => $first->page_group_key,
            'deployment_id' => $first->deployment_id,
            'build_id' => $first->build_id,
            'metric_name' => $first->metric_name,
            'metric_unit' => $first->metric_unit,
            'device_class' => $first->device_class,
            'sample_count' => $sampleCount,
            'p50_value' => $this->percentileContinuous($group, 0.50),
            'p75_value' => $this->percentileContinuous($group, 0.75),
            'good_count' => $group->where('rating', 'good')->count(),
            'needs_improvement_count' => $group->where('rating', 'needs_improvement')->count(),
            'poor_count' => $group->where('rating', 'poor')->count(),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param  Collection<int, VitalsEvent>  $group
     * @return array<string, mixed>
     */
    private function mapDeploymentRollup(Collection $group): array
    {
        $first = $group->firstOrFail();
        $sortedByTime = $group->sortBy('occurred_at')->values();
        $sampleCount = $group->count();
        $now = now();

        return [
            'deployment_id' => $first->deployment_id,
            'site_id' => $first->site_id,
            'environment' => $first->environment,
            'page_group_key' => $first->page_group_key,
            'build_id' => $first->build_id,
            'release_version' => $first->release_version,
            'metric_name' => $first->metric_name,
            'metric_unit' => $first->metric_unit,
            'device_class' => $first->device_class,
            'first_seen_at' => $sortedByTime->firstOrFail()->occurred_at,
            'last_seen_at' => $sortedByTime->last()->occurred_at,
            'sample_count' => $sampleCount,
            'p50_value' => $this->percentileContinuous($group, 0.50),
            'p75_value' => $this->percentileContinuous($group, 0.75),
            'good_count' => $group->where('rating', 'good')->count(),
            'needs_improvement_count' => $group->where('rating', 'needs_improvement')->count(),
            'poor_count' => $group->where('rating', 'poor')->count(),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param  Collection<int, VitalsEvent>  $group
     */
    private function percentileContinuous(Collection $group, float $percentile): float
    {
        $values = $group
            ->map(fn (VitalsEvent $event): float => (float) $event->metric_value)
            ->sort()
            ->values();

        $count = $values->count();

        if ($count === 0) {
            return 0.0;
        }

        if ($count === 1) {
            return round($values->first(), 3);
        }

        $position = ($count - 1) * $percentile;
        $lowerIndex = (int) floor($position);
        $upperIndex = (int) ceil($position);
        $fraction = $position - $lowerIndex;

        $lowerValue = $values->get($lowerIndex);
        $upperValue = $values->get($upperIndex);

        if ($lowerIndex === $upperIndex) {
            return round($lowerValue, 3);
        }

        return round($lowerValue + (($upperValue - $lowerValue) * $fraction), 3);
    }
}
