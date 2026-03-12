<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'slug' => Str::slug(fake()->unique()->domainWord()),
            'name' => fake()->company().' Performance',
            'default_environment' => 'production',
            'timezone' => 'UTC',
            'status' => 'active',
            'ingest_key_hash' => Hash::make('test-ingest-key'),
        ];
    }

    public function withIngestKey(string $plainTextKey): static
    {
        return $this->state(fn (array $attributes) => [
            'ingest_key_hash' => Hash::make($plainTextKey),
        ]);
    }
}
