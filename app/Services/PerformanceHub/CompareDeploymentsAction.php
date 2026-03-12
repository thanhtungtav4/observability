<?php

namespace App\Services\PerformanceHub;

use App\Models\Deployment;
use App\Models\DeploymentMetricRollup;
use App\Models\Site;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CompareDeploymentsAction
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{currentDeployment: Deployment, baselineDeployment: Deployment, metrics: list<array<string, mixed>>}
     */
    public function __invoke(string $siteId, array $filters): array
    {
        $site = Site::query()->findOrFail($siteId);

        $currentDeployment = Deployment::query()
            ->where('site_id', $site->id)
            ->findOrFail($filters['currentDeploymentId']);

        $baselineDeployment = isset($filters['baselineDeploymentId'])
            ? Deployment::query()->where('site_id', $site->id)->findOrFail($filters['baselineDeploymentId'])
            : Deployment::query()
                ->where('site_id', $site->id)
                ->where('environment', $currentDeployment->environment)
                ->where('deployed_at', '<', $currentDeployment->deployed_at)
                ->orderByDesc('deployed_at')
                ->first();

        if (! $baselineDeployment instanceof Deployment) {
            throw ValidationException::withMessages([
                'baselineDeploymentId' => 'A baseline deployment could not be resolved for this comparison.',
            ]);
        }

        $currentRollups = $this->rollupsForDeployment($currentDeployment, $filters['deviceClass'] ?? null);
        $baselineRollups = $this->rollupsForDeployment($baselineDeployment, $filters['deviceClass'] ?? null);

        $metrics = $currentRollups
            ->intersectByKeys($baselineRollups)
            ->map(function (DeploymentMetricRollup $currentRollup, string $key) use ($baselineRollups): array {
                $baselineRollup = $baselineRollups->get($key);

                return [
                    'metricName' => $currentRollup->metric_name,
                    'deviceClass' => $currentRollup->device_class,
                    'pageGroupKey' => $currentRollup->page_group_key,
                    'baselineP75' => (float) $baselineRollup->p75_value,
                    'currentP75' => (float) $currentRollup->p75_value,
                    'delta' => round((float) $currentRollup->p75_value - (float) $baselineRollup->p75_value, 3),
                    'baselineSampleCount' => $baselineRollup->sample_count,
                    'currentSampleCount' => $currentRollup->sample_count,
                ];
            })
            ->values()
            ->all();

        return [
            'currentDeployment' => $currentDeployment,
            'baselineDeployment' => $baselineDeployment,
            'metrics' => $metrics,
        ];
    }

    /**
     * @return Collection<string, DeploymentMetricRollup>
     */
    private function rollupsForDeployment(Deployment $deployment, ?string $deviceClass): Collection
    {
        return DeploymentMetricRollup::query()
            ->where('deployment_id', $deployment->id)
            ->when(
                $deviceClass,
                fn ($query, string $deviceClass) => $query->where('device_class', $deviceClass)
            )
            ->get()
            ->keyBy(fn (DeploymentMetricRollup $rollup): string => implode('|', [
                $rollup->metric_name,
                $rollup->device_class,
                $rollup->page_group_key,
            ]));
    }
}
