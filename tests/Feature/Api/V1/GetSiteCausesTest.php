<?php

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\SyntheticRun;
use App\Models\VitalsEvent;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    config([
        'services.performance_hub.internal_token' => 'internal-secret',
    ]);
});

it('returns likely-cause signals for a site window', function () {
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

    $lcpEvent = VitalsEvent::factory()
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
            'metric_value' => 4200,
            'delta_value' => 4200,
            'rating' => 'poor',
            'device_class' => 'mobile',
            'attribution' => [
                'timeToFirstByte' => 510,
                'resourceLoadDelay' => 200,
                'resourceLoadDuration' => 1820,
                'elementRenderDelay' => 340,
            ],
        ]);

    $lcpEvent->resources()->createMany([
        [
            'resource_url' => 'https://assets.smile.example.com/images/hero.jpg',
            'resource_host' => 'assets.smile.example.com',
            'resource_path' => '/images/hero.jpg',
            'resource_type' => 'image',
            'duration_ms' => 1180,
            'transfer_size' => 420000,
            'render_blocking' => false,
            'is_lcp_candidate' => true,
        ],
        [
            'resource_url' => 'https://app.smile.example.com/build/app.css',
            'resource_host' => 'app.smile.example.com',
            'resource_path' => '/build/app.css',
            'resource_type' => 'stylesheet',
            'duration_ms' => 210,
            'transfer_size' => 92000,
            'render_blocking' => true,
            'is_lcp_candidate' => false,
        ],
    ]);

    $inpEvent = VitalsEvent::factory()
        ->for($site)
        ->create([
            'deployment_id' => $deployment->id,
            'page_group_id' => $pageGroup->id,
            'page_group_key' => 'home',
            'environment' => 'production',
            'occurred_at' => '2026-03-12 02:00:00',
            'build_id' => $deployment->build_id,
            'metric_name' => 'inp',
            'metric_unit' => 'ms',
            'metric_value' => 360,
            'delta_value' => 360,
            'rating' => 'needs_improvement',
            'device_class' => 'mobile',
            'attribution' => [
                'inputDelay' => 44,
                'processingDuration' => 208,
                'presentationDelay' => 72,
            ],
        ]);

    $inpEvent->longTasks()->createMany([
        [
            'name' => 'script-evaluation',
            'script_url' => 'https://app.smile.example.com/build/app.js',
            'script_host' => 'app.smile.example.com',
            'invoker_type' => 'event-listener',
            'container_selector' => 'button[data-booking-cta]',
            'duration_ms' => 280,
            'blocking_duration_ms' => 180,
        ],
        [
            'name' => 'layout',
            'script_url' => 'https://app.smile.example.com/build/forms.js',
            'script_host' => 'app.smile.example.com',
            'invoker_type' => 'promise',
            'container_selector' => '#booking-form',
            'duration_ms' => 190,
            'blocking_duration_ms' => 96,
        ],
    ]);

    $inpEvent->javascriptErrors()->create([
        'fingerprint' => sha1('hydrate-home'),
        'name' => 'TypeError',
        'message' => 'Cannot read properties of undefined during hydration',
        'source_url' => 'https://app.smile.example.com/build/app.js',
        'source_host' => 'app.smile.example.com',
        'handled' => false,
    ]);

    VitalsEvent::factory()
        ->for($site)
        ->create([
            'deployment_id' => $deployment->id,
            'page_group_id' => $pageGroup->id,
            'page_group_key' => 'home',
            'environment' => 'production',
            'occurred_at' => '2026-03-12 03:00:00',
            'build_id' => $deployment->build_id,
            'metric_name' => 'cls',
            'metric_unit' => 'score',
            'metric_value' => 0.184,
            'delta_value' => 0.184,
            'rating' => 'needs_improvement',
            'device_class' => 'mobile',
            'attribution' => [
                'largestShiftTarget' => '#promo-banner',
            ],
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
            'opportunities' => [
                ['id' => 'main-thread-tasks', 'title' => 'Reduce main-thread work during interaction'],
                ['id' => 'render-blocking-resources', 'title' => 'Eliminate render-blocking resources'],
            ],
        ]);

    $response = $this->withToken('internal-secret')->getJson("/api/v1/sites/{$site->id}/causes?from=2026-03-12&to=2026-03-12&environment=production&deviceClass=mobile&pageGroupKey=home");

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('site.id', $site->id)
            ->where('signals.summary.eventCount', 3)
            ->where('signals.summary.resourceCount', 2)
            ->where('signals.summary.longTaskCount', 2)
            ->where('signals.summary.errorCount', 1)
            ->where('signals.phaseBreakdown.0.metricName', 'lcp')
            ->where('signals.phaseBreakdown.0.rows.0.phase', 'Resource load duration')
            ->where('signals.layoutShiftHotspots.0.target', '#promo-banner')
            ->where('signals.resourceHotspots.0.host', 'assets.smile.example.com')
            ->where('signals.interactionHotspots.0.scriptHost', 'app.smile.example.com')
            ->where('signals.errorHotspots.0.name', 'TypeError')
            ->where('signals.labOpportunities.0.title', 'Reduce main-thread work during interaction')
            ->etc()
        );
});
