<?php

use App\Models\Site;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    config([
        'services.performance_hub.internal_token' => 'internal-secret',
    ]);
});

it('lists all sites for the internal api consumer', function () {
    Site::factory()->create([
        'slug' => 'smile-clinic',
        'name' => 'Smile Clinic',
    ]);

    Site::factory()->create([
        'slug' => 'acme-store',
        'name' => 'Acme Store',
    ]);

    $response = $this->withToken('internal-secret')->getJson('/api/v1/sites');

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 2)
            ->whereType('data.0.id', 'string')
            ->whereType('data.0.teamId', 'string')
            ->whereType('data.0.slug', 'string')
            ->whereType('data.0.name', 'string')
            ->etc()
        );
});

it('rejects site listing without the internal token', function () {
    $response = $this->getJson('/api/v1/sites');

    $response->assertUnauthorized();
});
