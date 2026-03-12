<?php

namespace Database\Factories;

use App\Models\VitalsEvent;
use App\Models\VitalsEventJavascriptError;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VitalsEventJavascriptError>
 */
class VitalsEventJavascriptErrorFactory extends Factory
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
            'fingerprint' => fake()->sha1(),
            'name' => fake()->randomElement(['TypeError', 'ReferenceError']),
            'message' => fake()->randomElement([
                'Cannot read properties of undefined',
                'Failed to execute querySelector on Document',
            ]),
            'source_url' => 'https://app.'.fake()->domainName().'/build/'.fake()->slug().'.js',
            'source_host' => 'app.'.fake()->domainName(),
            'line_number' => fake()->numberBetween(1, 320),
            'column_number' => fake()->numberBetween(1, 180),
            'handled' => fake()->boolean(),
            'stack' => "TypeError: Example\n    at hydrate (app.js:42:11)",
        ];
    }
}
