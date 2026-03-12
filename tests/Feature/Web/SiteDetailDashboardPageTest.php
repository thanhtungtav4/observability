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
            'attribution' => [
                'timeToFirstByte' => 420,
                'resourceLoadDelay' => 160,
                'resourceLoadDuration' => 1490,
                'elementRenderDelay' => 260,
            ],
        ]);

    $event = VitalsEvent::query()->firstOrFail();

    $event->resources()->create([
        'resource_url' => 'https://assets.smile.example.com/images/hero.jpg',
        'resource_host' => 'assets.smile.example.com',
        'resource_path' => '/images/hero.jpg',
        'resource_type' => 'image',
        'duration_ms' => 1180,
        'transfer_size' => 420000,
        'render_blocking' => false,
        'is_lcp_candidate' => true,
    ]);

    $event->javascriptErrors()->create([
        'fingerprint' => sha1('hydrate-home'),
        'name' => 'TypeError',
        'message' => 'Cannot read properties of undefined during hydration',
        'source_url' => 'https://app.smile.example.com/build/app.js',
        'source_host' => 'app.smile.example.com',
        'handled' => false,
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
        ->assertSeeText('Likely causes')
        ->assertSeeText('Hot resources')
        ->assertSeeText('Recent JavaScript faults')
        ->assertSeeText('assets.smile.example.com')
        ->assertSeeText('Latest synthetic evidence');
});
