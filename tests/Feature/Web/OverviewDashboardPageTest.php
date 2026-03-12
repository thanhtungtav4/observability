<?php

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\VitalsEvent;

it('renders the portfolio overview dashboard', function () {
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
                'occurred_at' => "2026-03-1".($offset + 1)." 01:00:00",
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

    $response = $this->get('/?from=2026-03-10&to=2026-03-12&environment=production');

    $response
        ->assertSuccessful()
        ->assertSeeText('Portfolio Pulse')
        ->assertSeeText('Smile Clinic')
        ->assertSeeText('Trend matrix');
});
