<?php

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\SyntheticRun;
use App\Models\User;
use App\Models\VitalsEvent;

it('renders the portfolio overview dashboard', function () {
    $admin = User::factory()->admin()->create();

    $site = Site::factory()->create([
        'slug' => 'smile-clinic',
        'name' => 'Smile Clinic',
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

    collect([2800, 3200])->each(function (int $metricValue, int $offset) use ($deployment, $pageGroup, $site): void {
        VitalsEvent::factory()
            ->for($site)
            ->create([
                'deployment_id' => $deployment->id,
                'page_group_id' => $pageGroup->id,
                'page_group_key' => 'home',
                'environment' => 'production',
                'occurred_at' => '2026-03-1'.($offset + 1).' 01:00:00',
                'build_id' => $deployment->build_id,
                'metric_name' => 'lcp',
                'metric_unit' => 'ms',
                'metric_value' => $metricValue,
                'delta_value' => $metricValue,
                'rating' => 'poor',
                'device_class' => 'mobile',
            ]);
    });

    $this->artisan('performance-hub:refresh-rollups')->assertExitCode(0);

    $response = $this
        ->actingAs($admin)
        ->get('/?from=2026-03-10&to=2026-03-12&environment=production');

    $response
        ->assertSuccessful()
        ->assertSeeText('Portfolio Pulse')
        ->assertSeeText('Smile Clinic')
        ->assertSeeText('Trend matrix');
});

it('filters overview stats by the selected environment', function () {
    $admin = User::factory()->admin()->create();

    $site = Site::factory()->create([
        'slug' => 'smile-clinic',
        'name' => 'Smile Clinic',
    ]);

    $productionDeployment = Deployment::factory()
        ->for($site)
        ->create([
            'environment' => 'production',
            'build_id' => '2026.03.12-prod',
        ]);

    $stagingDeployment = Deployment::factory()
        ->for($site)
        ->create([
            'environment' => 'staging',
            'build_id' => '2026.03.12-staging',
        ]);

    $pageGroup = PageGroup::factory()
        ->for($site)
        ->create([
            'group_key' => 'home',
        ]);

    foreach (['production' => $productionDeployment, 'staging' => $stagingDeployment] as $environment => $deployment) {
        collect([2100, 2300])->each(function (int $metricValue, int $offset) use ($deployment, $environment, $pageGroup, $site): void {
            VitalsEvent::factory()
                ->for($site)
                ->create([
                    'deployment_id' => $deployment->id,
                    'page_group_id' => $pageGroup->id,
                    'page_group_key' => 'home',
                    'environment' => $environment,
                    'occurred_at' => '2026-03-12 0'.($offset + 1).':00:00',
                    'build_id' => $deployment->build_id,
                    'metric_name' => 'lcp',
                    'metric_unit' => 'ms',
                    'metric_value' => $metricValue,
                    'delta_value' => $metricValue,
                    'rating' => 'good',
                    'device_class' => 'mobile',
                ]);
        });
    }

    SyntheticRun::factory()
        ->for($site)
        ->create([
            'deployment_id' => $productionDeployment->id,
            'page_group_id' => $pageGroup->id,
            'page_group_key' => 'home',
            'environment' => 'production',
            'build_id' => $productionDeployment->build_id,
        ]);

    SyntheticRun::factory()
        ->for($site)
        ->create([
            'deployment_id' => $stagingDeployment->id,
            'page_group_id' => $pageGroup->id,
            'page_group_key' => 'home',
            'environment' => 'staging',
            'build_id' => $stagingDeployment->build_id,
        ]);

    $this->artisan('performance-hub:refresh-rollups')->assertExitCode(0);

    $response = $this
        ->actingAs($admin)
        ->get('/?from=2026-03-12&to=2026-03-12&environment=production');

    $response
        ->assertSuccessful()
        ->assertViewHas('stats', fn (array $stats): bool => $stats['eventCount'] === 2 && $stats['syntheticCount'] === 1);
});
