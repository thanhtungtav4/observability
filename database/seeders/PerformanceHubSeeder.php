<?php

namespace Database\Seeders;

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Models\Team;
use Illuminate\Database\Seeder;

class PerformanceHubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $team = Team::factory()->create([
            'slug' => 'pilot-team',
            'name' => 'Pilot Team',
        ]);

        $site = Site::factory()
            ->for($team)
            ->withIngestKey('pilot-ingest-key')
            ->create([
                'slug' => 'smile-clinic',
                'name' => 'Smile Clinic',
            ]);

        SiteDomain::factory()
            ->for($site)
            ->primary()
            ->create([
                'domain' => 'smile-clinic.test',
            ]);

        PageGroup::factory()
            ->for($site)
            ->create([
                'group_key' => 'home',
                'label' => 'Home',
                'match_rules' => [
                    [
                        'type' => 'literal',
                        'value' => '/',
                    ],
                ],
                'pattern_type' => 'literal',
            ]);

        Deployment::factory()
            ->for($site)
            ->create([
                'build_id' => '2026.03.12-1',
                'release_version' => '2026.03.12',
                'git_ref' => 'main',
            ]);
    }
}
