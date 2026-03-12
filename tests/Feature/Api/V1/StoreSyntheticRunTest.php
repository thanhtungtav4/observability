<?php

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    config([
        'services.performance_hub.internal_token' => 'internal-secret',
    ]);
});

it('stores a synthetic run and links matching deployment context', function () {
    $site = Site::factory()->create([
        'slug' => 'smile-clinic',
    ]);

    $deployment = Deployment::factory()
        ->for($site)
        ->create([
            'environment' => 'production',
            'build_id' => '2026.03.12-1',
            'release_version' => '2026.03.12',
        ]);

    $pageGroup = PageGroup::factory()
        ->for($site)
        ->create([
            'group_key' => 'home',
        ]);

    $response = $this->withToken('internal-secret')->postJson('/api/v1/synthetic-runs', [
        'siteKey' => 'smile-clinic',
        'environment' => 'production',
        'buildId' => '2026.03.12-1',
        'occurredAt' => '2026-03-12T03:00:00Z',
        'pageUrl' => 'https://smile.example.com/',
        'pagePath' => '/',
        'pageGroupKey' => 'home',
        'devicePreset' => 'mobile',
        'performanceScore' => 71.3,
        'accessibilityScore' => 97,
        'bestPracticesScore' => 92,
        'seoScore' => 94,
        'fcpMs' => 1200,
        'lcpMs' => 2800,
        'tbtMs' => 180,
        'clsScore' => 0.04,
        'speedIndexMs' => 1600,
        'inpMs' => 190,
        'opportunities' => [
            [
                'id' => 'render-blocking-resources',
                'title' => 'Eliminate render-blocking resources',
            ],
        ],
        'diagnostics' => [
            'userAgent' => 'Lighthouse',
        ],
        'reportUrl' => 'https://reports.example.com/run-1',
    ]);

    $response
        ->assertCreated()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('siteId', $site->id)
            ->where('buildId', '2026.03.12-1')
            ->where('pageGroupKey', 'home')
            ->where('devicePreset', 'mobile')
            ->etc()
        );

    $this->assertDatabaseHas('synthetic_runs', [
        'site_id' => $site->id,
        'deployment_id' => $deployment->id,
        'page_group_id' => $pageGroup->id,
        'page_group_key' => 'home',
        'build_id' => '2026.03.12-1',
        'runner' => 'lighthouse',
        'device_preset' => 'mobile',
    ]);
});

it('rejects synthetic run writes without the internal token', function () {
    Site::factory()->create([
        'slug' => 'smile-clinic',
    ]);

    $response = $this->postJson('/api/v1/synthetic-runs', [
        'siteKey' => 'smile-clinic',
        'environment' => 'production',
        'buildId' => '2026.03.12-1',
        'occurredAt' => '2026-03-12T03:00:00Z',
        'pageUrl' => 'https://smile.example.com/',
        'pagePath' => '/',
        'pageGroupKey' => 'home',
        'devicePreset' => 'mobile',
        'performanceScore' => 71.3,
        'opportunities' => [],
        'diagnostics' => [],
    ]);

    $response->assertUnauthorized();
});
