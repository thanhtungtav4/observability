<?php

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\VitalsEvent;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    config([
        'services.performance_hub.internal_token' => 'internal-secret',
    ]);
});

it('compares the current deployment against the previous deployment rollups', function () {
    $site = Site::factory()->create([
        'slug' => 'smile-clinic',
    ]);

    $pageGroup = PageGroup::factory()
        ->for($site)
        ->create([
            'group_key' => 'home',
        ]);

    $baselineDeployment = Deployment::factory()
        ->for($site)
        ->create([
            'environment' => 'production',
            'build_id' => '2026.03.11-1',
            'deployed_at' => '2026-03-11 01:00:00',
        ]);

    $currentDeployment = Deployment::factory()
        ->for($site)
        ->create([
            'environment' => 'production',
            'build_id' => '2026.03.12-1',
            'deployed_at' => '2026-03-12 01:00:00',
        ]);

    collect([2000, 2200, 2400, 2600])->each(function (int $metricValue, int $offset) use ($baselineDeployment, $pageGroup, $site): void {
        VitalsEvent::factory()
            ->for($site)
            ->create([
                'deployment_id' => $baselineDeployment->id,
                'page_group_id' => $pageGroup->id,
                'page_group_key' => 'home',
                'environment' => 'production',
                'occurred_at' => "2026-03-11 0".($offset + 1).":00:00",
                'build_id' => $baselineDeployment->build_id,
                'release_version' => $baselineDeployment->release_version,
                'git_ref' => $baselineDeployment->git_ref,
                'git_commit_sha' => $baselineDeployment->git_commit_sha,
                'metric_name' => 'lcp',
                'metric_unit' => 'ms',
                'metric_value' => $metricValue,
                'delta_value' => $metricValue,
                'rating' => $metricValue > 2500 ? 'poor' : 'good',
                'device_class' => 'mobile',
            ]);
    });

    collect([3000, 3200, 3400, 3600])->each(function (int $metricValue, int $offset) use ($currentDeployment, $pageGroup, $site): void {
        VitalsEvent::factory()
            ->for($site)
            ->create([
                'deployment_id' => $currentDeployment->id,
                'page_group_id' => $pageGroup->id,
                'page_group_key' => 'home',
                'environment' => 'production',
                'occurred_at' => "2026-03-12 0".($offset + 1).":00:00",
                'build_id' => $currentDeployment->build_id,
                'release_version' => $currentDeployment->release_version,
                'git_ref' => $currentDeployment->git_ref,
                'git_commit_sha' => $currentDeployment->git_commit_sha,
                'metric_name' => 'lcp',
                'metric_unit' => 'ms',
                'metric_value' => $metricValue,
                'delta_value' => $metricValue,
                'rating' => 'poor',
                'device_class' => 'mobile',
            ]);
    });

    $this->artisan('performance-hub:refresh-rollups')->assertExitCode(0);

    $response = $this->withToken('internal-secret')->getJson("/api/v1/sites/{$site->id}/deployments/compare?currentDeploymentId={$currentDeployment->id}");

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('currentDeployment.id', $currentDeployment->id)
            ->where('baselineDeployment.id', $baselineDeployment->id)
            ->has('metrics', 1)
            ->where('metrics.0.metricName', 'lcp')
            ->where('metrics.0.deviceClass', 'mobile')
            ->where('metrics.0.pageGroupKey', 'home')
            ->where('metrics.0.baselineP75', 2450)
            ->where('metrics.0.currentP75', 3450)
            ->where('metrics.0.delta', 1000)
            ->etc()
        );
});
