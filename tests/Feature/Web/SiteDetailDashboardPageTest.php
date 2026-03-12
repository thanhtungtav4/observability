<?php

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\SyntheticRun;
use App\Models\User;
use App\Models\VitalsEvent;

it('renders the site detail dashboard', function () {
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

    VitalsEvent::factory()
        ->for($site)
        ->create([
            'deployment_id' => $deployment->id,
            'page_group_id' => $pageGroup->id,
            'page_group_key' => 'home',
            'environment' => 'production',
            'occurred_at' => '2026-03-12 01:00:00',
            'build_id' => $deployment->build_id,
            'metric_name' => 'lcp',
            'metric_unit' => 'ms',
            'metric_value' => 2600,
            'delta_value' => 2600,
            'rating' => 'poor',
            'device_class' => 'mobile',
        ]);

    SyntheticRun::factory()
        ->for($site)
        ->create([
            'deployment_id' => $deployment->id,
            'page_group_id' => $pageGroup->id,
            'page_group_key' => 'home',
            'environment' => 'production',
            'build_id' => $deployment->build_id,
            'device_preset' => 'mobile',
            'performance_score' => 73.5,
        ]);

    $this->artisan('performance-hub:refresh-rollups')->assertExitCode(0);

    $response = $this
        ->actingAs($admin)
        ->get("/sites/{$site->id}?from=2026-03-10&to=2026-03-12");

    $response
        ->assertSuccessful()
        ->assertSeeText('Site Detail')
        ->assertSeeText('Smile Clinic')
        ->assertSeeText('Latest synthetic evidence');
});
