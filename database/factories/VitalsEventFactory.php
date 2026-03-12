<?php

namespace Database\Factories;

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\VitalsEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VitalsEvent>
 */
class VitalsEventFactory extends Factory
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
            'metric_name' => 'lcp',
            'metric_unit' => 'ms',
            'metric_value' => 2400,
            'delta_value' => 2400,
            'rating' => 'good',
            'url' => 'https://'.fake()->domainName().'/',
            'path' => '/',
            'page_title' => fake()->sentence(3),
            'device_class' => 'mobile',
            'navigation_type' => 'navigate',
            'browser_name' => 'Chrome',
            'browser_version' => '122',
            'os_name' => 'Android',
            'country_code' => 'VN',
            'effective_connection_type' => '4g',
            'round_trip_time_ms' => 120,
            'downlink_mbps' => 12.5,
            'session_id' => fake()->uuid(),
            'page_view_id' => fake()->uuid(),
            'visitor_hash' => fake()->sha1(),
            'attribution' => [
                'lcpElement' => 'img.hero',
            ],
            'tags' => [
                'countryCode' => 'VN',
            ],
        ];
    }
}
