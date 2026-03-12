<?php

use App\Models\Site;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    config([
        'services.performance_hub.internal_token' => 'internal-secret',
    ]);
});

it('creates or updates a deployment with the internal token', function () {
    $site = Site::factory()->create([
        'slug' => 'smile-clinic',
    ]);

    $response = $this->withToken('internal-secret')->postJson('/api/v1/deployments', [
        'siteKey' => 'smile-clinic',
        'environment' => 'production',
        'buildId' => '2026.03.12-1',
        'releaseVersion' => '2026.03.12',
        'gitRef' => 'main',
        'gitCommitSha' => 'abc123def456',
        'deployedAt' => '2026-03-12T02:10:00Z',
        'actorName' => 'CI Bot',
        'ciSource' => 'github-actions',
        'metadata' => [
            'workflow' => 'deploy-production',
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('siteId', $site->id)
            ->where('environment', 'production')
            ->where('buildId', '2026.03.12-1')
            ->where('releaseVersion', '2026.03.12')
            ->etc()
        );

    $this->assertDatabaseHas('deployments', [
        'site_id' => $site->id,
        'environment' => 'production',
        'build_id' => '2026.03.12-1',
        'ci_source' => 'github-actions',
    ]);
});

it('rejects deployment writes without the internal token', function () {
    Site::factory()->create([
        'slug' => 'smile-clinic',
    ]);

    $response = $this->postJson('/api/v1/deployments', [
        'siteKey' => 'smile-clinic',
        'environment' => 'production',
        'buildId' => '2026.03.12-1',
        'deployedAt' => '2026-03-12T02:10:00Z',
    ]);

    $response->assertUnauthorized();
});
