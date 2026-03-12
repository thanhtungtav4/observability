<?php

namespace Database\Factories;

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\SyntheticRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyntheticRun>
 */
class SyntheticRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'deployment_id' => Deployment::factory(),
            'page_group_id' => PageGroup::factory(),
            'page_group_key' => 'home',
            'environment' => 'production',
            'occurred_at' => now(),
            'build_id' => fake()->bothify('build-####'),
            'release_version' => fake()->numerify('2026.03.##'),
            'git_ref' => 'main',
            'git_commit_sha' => fake()->sha1(),
            'runner' => 'lighthouse',
            'device_preset' => 'mobile',
            'page_url' => 'https://'.fake()->domainName().'/',
            'page_path' => '/',
            'performance_score' => 72.3,
            'accessibility_score' => 98.0,
            'best_practices_score' => 92.0,
            'seo_score' => 95.0,
            'fcp_ms' => 1200,
            'lcp_ms' => 2800,
            'tbt_ms' => 150,
            'cls_score' => 0.05,
            'speed_index_ms' => 1800,
            'inp_ms' => 180,
            'opportunities' => [
                [
                    'id' => 'render-blocking-resources',
                    'title' => 'Eliminate render-blocking resources',
                ],
            ],
            'diagnostics' => [
                'userAgent' => 'Lighthouse',
            ],
            'screenshot_url' => fake()->url(),
            'trace_url' => fake()->url(),
            'report_url' => fake()->url(),
        ];
    }
}
