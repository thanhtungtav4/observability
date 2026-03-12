<?php

namespace Database\Factories;

use App\Models\VitalsEvent;
use App\Models\VitalsEventLongTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VitalsEventLongTask>
 */
class VitalsEventLongTaskFactory extends Factory
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
            'name' => fake()->randomElement(['layout', 'script-evaluation', 'style-recalc']),
            'script_url' => 'https://app.'.fake()->domainName().'/build/'.fake()->slug().'.js',
            'script_host' => 'app.'.fake()->domainName(),
            'invoker_type' => fake()->randomElement(['event-listener', 'timer', 'promise']),
            'container_selector' => fake()->randomElement(['button[data-booking-cta]', '#checkout-form', '.hero-carousel']),
            'start_time_ms' => fake()->randomFloat(3, 100, 2500),
            'duration_ms' => fake()->randomFloat(3, 60, 1200),
            'blocking_duration_ms' => fake()->randomFloat(3, 35, 640),
        ];
    }
}
