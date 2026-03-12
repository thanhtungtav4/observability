<?php

namespace Database\Factories;

use App\Models\Deployment;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deployment>
 */
class DeploymentFactory extends Factory
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
            'environment' => 'production',
            'build_id' => fake()->unique()->bothify('build-####'),
            'release_version' => fake()->numerify('2026.03.##'),
            'git_ref' => 'main',
            'git_commit_sha' => fake()->sha1(),
            'deployed_at' => now()->subMinutes(fake()->numberBetween(5, 120)),
            'actor_name' => fake()->name(),
            'ci_source' => 'github-actions',
            'metadata' => [
                'workflow' => 'deploy',
            ],
        ];
    }
}
