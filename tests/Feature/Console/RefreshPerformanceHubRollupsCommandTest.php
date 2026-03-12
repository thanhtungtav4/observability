<?php

use App\Models\DailyMetricRollup;
use App\Models\Deployment;
use App\Models\DeploymentMetricRollup;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\VitalsEvent;

it('builds daily and deployment rollups from raw vitals events', function () {
    $site = Site::factory()->create([
        'slug' => 'smile-clinic',
    ]);

    $deployment = Deployment::factory()
        ->for($site)
        ->create([
            'environment' => 'production',
            'build_id' => '2026.03.12-1',
        ]);

    $pageGroup = PageGroup::factory()
        ->for($site)
        ->create([
            'group_key' => 'home',
        ]);

    collect([100, 200, 300, 400])->each(function (int $metricValue, int $offset) use ($deployment, $pageGroup, $site): void {
        VitalsEvent::factory()
            ->for($site)
            ->create([
                'deployment_id' => $deployment->id,
                'page_group_id' => $pageGroup->id,
                'page_group_key' => 'home',
                'environment' => 'production',
                'occurred_at' => now()->startOfDay()->addMinutes($offset),
                'build_id' => $deployment->build_id,
                'release_version' => $deployment->release_version,
                'git_ref' => $deployment->git_ref,
                'git_commit_sha' => $deployment->git_commit_sha,
                'metric_name' => 'lcp',
                'metric_unit' => 'ms',
                'metric_value' => $metricValue,
                'delta_value' => $metricValue,
                'rating' => $metricValue >= 400 ? 'poor' : 'good',
                'device_class' => 'mobile',
            ]);
    });

    $this->artisan('performance-hub:refresh-rollups')->assertExitCode(0);

    $dailyRollup = DailyMetricRollup::query()->firstOrFail();
    $deploymentRollup = DeploymentMetricRollup::query()->firstOrFail();

    expect($dailyRollup->sample_count)->toBe(4)
        ->and((float) $dailyRollup->p50_value)->toBe(250.0)
        ->and((float) $dailyRollup->p75_value)->toBe(325.0)
        ->and($dailyRollup->poor_count)->toBe(1)
        ->and($deploymentRollup->sample_count)->toBe(4)
        ->and((float) $deploymentRollup->p75_value)->toBe(325.0);
});
