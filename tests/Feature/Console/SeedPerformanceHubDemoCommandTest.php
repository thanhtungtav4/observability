<?php

use App\Models\DailyMetricRollup;
use App\Models\Deployment;
use App\Models\DeploymentMetricRollup;
use App\Models\Site;
use App\Models\SyntheticRun;
use App\Models\Team;
use App\Models\VitalsEvent;
use Carbon\Carbon;

it('seeds demo performance data and refreshes rollups', function () {
    Carbon::setTestNow('2026-03-12 09:00:00');

    $this->artisan('performance-hub:seed-demo')
        ->expectsOutputToContain('Seeded 3 sites, 9 deployments, 1890 vitals events, and 54 synthetic runs.')
        ->expectsOutputToContain('Refreshed 630 daily rollups and 270 deployment rollups.')
        ->assertSuccessful();

    expect(Team::query()->count())->toBe(1)
        ->and(Site::query()->count())->toBe(3)
        ->and(Deployment::query()->count())->toBe(9)
        ->and(VitalsEvent::query()->count())->toBe(1890)
        ->and(SyntheticRun::query()->count())->toBe(54)
        ->and(DailyMetricRollup::query()->count())->toBe(630)
        ->and(DeploymentMetricRollup::query()->count())->toBe(270);

    Carbon::setTestNow();
});

it('requires fresh mode before replacing existing performance hub data', function () {
    Site::factory()->create([
        'slug' => 'existing-site',
        'name' => 'Existing Site',
    ]);

    $this->artisan('performance-hub:seed-demo')
        ->expectsOutputToContain('Re-run with --fresh to replace it.')
        ->assertFailed();
});

it('replaces existing performance hub data when fresh mode is enabled', function () {
    Carbon::setTestNow('2026-03-12 09:00:00');

    Site::factory()->create([
        'slug' => 'existing-site',
        'name' => 'Existing Site',
    ]);

    $this->artisan('performance-hub:seed-demo --fresh')
        ->expectsOutputToContain('Cleared existing Performance Hub records.')
        ->expectsOutputToContain('Seeded 3 sites, 9 deployments, 1890 vitals events, and 54 synthetic runs.')
        ->assertSuccessful();

    expect(Site::query()->count())->toBe(3)
        ->and(Site::query()->where('slug', 'existing-site')->doesntExist())->toBeTrue();

    Carbon::setTestNow();
});
