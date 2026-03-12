<?php

namespace Database\Factories;

use App\Models\VitalsEvent;
use App\Models\VitalsEventResource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VitalsEventResource>
 */
class VitalsEventResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vitals_event_id' => VitalsEvent::factory(),
            'resource_url' => 'https://cdn.'.fake()->domainName().'/assets/'.fake()->slug().'.js',
            'resource_host' => 'cdn.'.fake()->domainName(),
            'resource_path' => '/assets/'.fake()->slug().'.js',
            'resource_type' => fake()->randomElement(['script', 'image', 'font', 'stylesheet']),
            'initiator_type' => fake()->randomElement(['script', 'img', 'link']),
            'duration_ms' => fake()->randomFloat(3, 25, 1800),
            'transfer_size' => fake()->numberBetween(40_000, 900_000),
            'decoded_body_size' => fake()->numberBetween(60_000, 1_400_000),
            'cache_state' => fake()->randomElement(['hit', 'miss', 'revalidated']),
            'priority' => fake()->randomElement(['high', 'medium', 'low']),
            'render_blocking' => fake()->boolean(35),
            'is_lcp_candidate' => fake()->boolean(20),
        ];
    }
}
