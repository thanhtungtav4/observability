<?php

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\VitalsEvent;
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
                'correlationId' => 'corr-home-001',
                'traceId' => 'trace-home-001',
                'release' => [
                    'buildId' => '2026.03.12-1',
                    'releaseVersion' => '2026.03.12',
                    'gitRef' => 'main',
                    'gitCommitSha' => 'abc123def456',
                    'deployedAt' => '2026-03-12T02:10:00Z',
                ],
                'attribution' => [
                    'lcpElement' => 'img.hero-image',
                    'timeToFirstByte' => 480,
                    'resourceLoadDelay' => 180,
                    'resourceLoadDuration' => 1760,
                    'elementRenderDelay' => 310,
                ],
                'tags' => [
                    'countryCode' => 'VN',
                    'experiment' => 'hero-v2',
                ],
                'context' => [
                    'collectorVersion' => '1.2.0',
                    'hydrationPhase' => 'after-hydration',
                    'routeTransitionType' => 'document',
                    'apiRequestKeys' => ['availability-api'],
                ],
                'resources' => [
                    [
                        'url' => 'https://cdn.smile.example.com/images/hero.jpg',
                        'resourceType' => 'image',
                        'initiatorType' => 'img',
                        'durationMs' => 1320,
                        'transferSize' => 420000,
                        'decodedBodySize' => 950000,
                        'cacheState' => 'miss',
                        'priority' => 'high',
                        'renderBlocking' => false,
                        'isLcpCandidate' => true,
                    ],
                ],
                'longTasks' => [
                    [
                        'name' => 'script-evaluation',
                        'scriptUrl' => 'https://app.smile.example.com/build/app.js',
                        'invokerType' => 'event-listener',
                        'containerSelector' => 'button[data-booking-cta]',
                        'startTimeMs' => 1440,
                        'durationMs' => 280,
                        'blockingDurationMs' => 180,
                    ],
                ],
                'errors' => [
                    [
                        'name' => 'TypeError',
                        'message' => 'Cannot read properties of undefined',
                        'sourceUrl' => 'https://app.smile.example.com/build/app.js',
                        'lineNumber' => 182,
                        'columnNumber' => 24,
                        'handled' => false,
                        'stack' => "TypeError: Cannot read properties of undefined\n    at hydrateBookingCta (app.js:182:24)",
                    ],
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
        'source_event_id' => '4ecf75c3-90c3-4de9-a51c-63a0f4b03e38',
        'correlation_id' => 'corr-home-001',
        'trace_id' => 'trace-home-001',
    ]);

    $this->assertDatabaseHas('vitals_event_resources', [
        'resource_host' => 'cdn.smile.example.com',
        'resource_type' => 'image',
        'is_lcp_candidate' => true,
    ]);

    $this->assertDatabaseHas('vitals_event_long_tasks', [
        'script_host' => 'app.smile.example.com',
        'container_selector' => 'button[data-booking-cta]',
    ]);

    $this->assertDatabaseHas('vitals_event_javascript_errors', [
        'name' => 'TypeError',
        'source_host' => 'app.smile.example.com',
        'handled' => false,
    ]);
});

it('deduplicates source events and refreshes attached evidence', function () {
    $site = Site::factory()
        ->withIngestKey('pilot-ingest-key')
        ->create([
            'slug' => 'smile-clinic',
        ]);

    Deployment::factory()
        ->for($site)
        ->create([
            'environment' => 'production',
            'build_id' => '2026.03.12-1',
        ]);

    PageGroup::factory()
        ->for($site)
        ->create([
            'group_key' => 'home',
        ]);

    $payload = [
        'siteKey' => 'smile-clinic',
        'environment' => 'production',
        'events' => [
            [
                'eventId' => '11111111-2222-3333-4444-555555555555',
                'occurredAt' => '2026-03-12T02:14:22.332Z',
                'metricName' => 'inp',
                'metricUnit' => 'ms',
                'metricValue' => 382,
                'deltaValue' => 382,
                'rating' => 'needs_improvement',
                'url' => 'https://smile.example.com/',
                'path' => '/',
                'pageGroupKey' => 'home',
                'deviceClass' => 'mobile',
                'release' => [
                    'buildId' => '2026.03.12-1',
                ],
                'attribution' => [
                    'inputDelay' => 42,
                    'processingDuration' => 210,
                    'presentationDelay' => 80,
                ],
                'tags' => [],
                'resources' => [
                    [
                        'url' => 'https://cdn.smile.example.com/images/hero.jpg',
                        'resourceType' => 'image',
                    ],
                ],
            ],
        ],
    ];

    $headers = ['X-Site-Ingest-Key' => 'pilot-ingest-key'];

    $this->withHeaders($headers)->postJson('/api/v1/collect/web-vitals', $payload)->assertAccepted();

    $payload['events'][0]['resources'] = [
        [
            'url' => 'https://cdn.smile.example.com/images/hero-v2.jpg',
            'resourceType' => 'image',
        ],
    ];

    $this->withHeaders($headers)->postJson('/api/v1/collect/web-vitals', $payload)->assertAccepted();

    expect(VitalsEvent::query()->count())->toBe(1);
    $this->assertDatabaseCount('vitals_event_resources', 1);
    $this->assertDatabaseHas('vitals_event_resources', [
        'resource_path' => '/images/hero-v2.jpg',
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
