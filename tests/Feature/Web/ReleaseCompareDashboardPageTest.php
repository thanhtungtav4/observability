<?php

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\VitalsEvent;

it('renders the release compare dashboard', function () {
    $site = Site::factory()->create([
        'slug' => 'smile-clinic',
        'name' => 'Smile Clinic',
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

    collect([2000, 2200])->each(function (int $metricValue, int $offset) use ($baselineDeployment, $pageGroup, $site): void {
        VitalsEvent::factory()
            ->for($site)
            ->create([
                'deployment_id' => $baselineDeployment->id,
                'page_group_id' => $pageGroup->id,
                'page_group_key' => 'home',
                'environment' => 'production',
                'occurred_at' => "2026-03-11 0".($offset + 1).":00:00",
                'build_id' => $baselineDeployment->build_id,
                'metric_name' => 'lcp',
                'metric_unit' => 'ms',
                'metric_value' => $metricValue,
                'delta_value' => $metricValue,
                'rating' => 'good',
                'device_class' => 'mobile',
            ]);
    });

    collect([3200, 3600])->each(function (int $metricValue, int $offset) use ($currentDeployment, $pageGroup, $site): void {
        VitalsEvent::factory()
            ->for($site)
            ->create([
                'deployment_id' => $currentDeployment->id,
                'page_group_id' => $pageGroup->id,
                'page_group_key' => 'home',
                'environment' => 'production',
                'occurred_at' => "2026-03-12 0".($offset + 1).":00:00",
                'build_id' => $currentDeployment->build_id,
                'metric_name' => 'lcp',
                'metric_unit' => 'ms',
                'metric_value' => $metricValue,
                'delta_value' => $metricValue,
                'rating' => 'poor',
                'device_class' => 'mobile',
            ]);
    });

    $this->artisan('performance-hub:refresh-rollups')->assertExitCode(0);

    $response = $this->get("/sites/{$site->id}/compare?currentDeploymentId={$currentDeployment->id}");

    $response
        ->assertSuccessful()
        ->assertSeeText('Release Compare')
        ->assertSeeText('Slice-by-slice delta table')
        ->assertSeeText('2026.03.12-1')
        ->assertSeeText('2026.03.11-1');
});
