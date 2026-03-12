<?php

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use Illuminate\Testing\Fluent\AssertableJson;

it('accepts web vitals events for a site with a valid ingest key', function () {
    $site = Site::factory()
        ->withIngestKey('pilot-ingest-key')
        ->create([
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

    $response = $this->withHeaders([
        'X-Site-Ingest-Key' => 'pilot-ingest-key',
    ])->postJson('/api/v1/collect/web-vitals', [
        'siteKey' => 'smile-clinic',
        'environment' => 'production',
        'events' => [
            [
                'eventId' => '4ecf75c3-90c3-4de9-a51c-63a0f4b03e38',
                'occurredAt' => '2026-03-12T02:14:22.332Z',
                'metricName' => 'lcp',
                'metricUnit' => 'ms',
                'metricValue' => 4212,
                'deltaValue' => 4212,
                'rating' => 'poor',
                'url' => 'https://smile.example.com/',
                'path' => '/',
                'pageTitle' => 'Smile Clinic',
                'pageGroupKey' => 'home',
                'deviceClass' => 'mobile',
                'navigationType' => 'navigate',
                'effectiveConnectionType' => '4g',
                'browserName' => 'Chrome',
                'browserVersion' => '122',
                'osName' => 'Android',
                'sessionId' => 'f46cc9df-92e8-441e-9052-583d07a8de9d',
                'pageViewId' => 'e10e7c53-e6d8-4567-b9eb-73506b2414fc',
                'release' => [
                    'buildId' => '2026.03.12-1',
                    'releaseVersion' => '2026.03.12',
                    'gitRef' => 'main',
                    'gitCommitSha' => 'abc123def456',
                    'deployedAt' => '2026-03-12T02:10:00Z',
                ],
                'attribution' => [
                    'lcpElement' => 'img.hero-image',
                ],
                'tags' => [
                    'countryCode' => 'VN',
                    'experiment' => 'hero-v2',
                ],
            ],
        ],
    ]);

    $response
        ->assertAccepted()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('accepted', 1)
            ->where('rejected', 0)
            ->whereType('ingestionBatchId', 'string')
            ->etc()
        );

    $this->assertDatabaseHas('vitals_events', [
        'site_id' => $site->id,
        'deployment_id' => $deployment->id,
        'page_group_id' => $pageGroup->id,
        'page_group_key' => 'home',
        'build_id' => '2026.03.12-1',
        'metric_name' => 'lcp',
        'device_class' => 'mobile',
        'country_code' => 'VN',
    ]);
});

it('rejects web vitals events with an invalid ingest key', function () {
    Site::factory()
        ->withIngestKey('pilot-ingest-key')
        ->create([
            'slug' => 'smile-clinic',
        ]);

    $response = $this->withHeaders([
        'X-Site-Ingest-Key' => 'wrong-key',
    ])->postJson('/api/v1/collect/web-vitals', [
        'siteKey' => 'smile-clinic',
        'environment' => 'production',
        'events' => [
            [
                'eventId' => '4ecf75c3-90c3-4de9-a51c-63a0f4b03e38',
                'occurredAt' => '2026-03-12T02:14:22.332Z',
                'metricName' => 'lcp',
                'metricUnit' => 'ms',
                'metricValue' => 4212,
                'rating' => 'poor',
                'url' => 'https://smile.example.com/',
                'path' => '/',
                'pageGroupKey' => 'home',
                'deviceClass' => 'mobile',
                'release' => [
                    'buildId' => '2026.03.12-1',
                ],
                'attribution' => [],
                'tags' => [],
            ],
        ],
    ]);

    $response->assertUnauthorized();
});
