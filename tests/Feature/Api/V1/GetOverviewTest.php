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

it('returns portfolio health and trend slices from refreshed rollups', function () {
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

    collect([
        ['2026-03-10 01:00:00', 3100, 'poor'],
        ['2026-03-10 02:00:00', 3300, 'poor'],
        ['2026-03-11 01:00:00', 2900, 'poor'],
        ['2026-03-11 02:00:00', 3100, 'poor'],
    ])->each(function (array $event) use ($deployment, $pageGroup, $site): void {
        VitalsEvent::factory()
            ->for($site)
            ->create([
                'deployment_id' => $deployment->id,
                'page_group_id' => $pageGroup->id,
                'page_group_key' => 'home',
                'environment' => 'production',
                'occurred_at' => $event[0],
                'build_id' => $deployment->build_id,
                'release_version' => $deployment->release_version,
                'git_ref' => $deployment->git_ref,
                'git_commit_sha' => $deployment->git_commit_sha,
                'metric_name' => 'lcp',
                'metric_unit' => 'ms',
                'metric_value' => $event[1],
                'delta_value' => $event[1],
                'rating' => $event[2],
                'device_class' => 'mobile',
            ]);
    });

    $this->artisan('performance-hub:refresh-rollups')->assertExitCode(0);

    $response = $this->withToken('internal-secret')->getJson('/api/v1/dashboard/overview?from=2026-03-10&to=2026-03-11&environment=production');

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('health', 1)
            ->has('trends', 2)
            ->where('health.0.siteSlug', 'smile-clinic')
            ->where('health.0.failingMetrics.0', 'lcp')
            ->where('trends.0.metricName', 'lcp')
            ->where('trends.0.deviceClass', 'mobile')
            ->etc()
        );
});
