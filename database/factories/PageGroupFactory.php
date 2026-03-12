<?php

namespace Database\Factories;

use App\Models\PageGroup;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PageGroup>
 */
class PageGroupFactory extends Factory
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
            'group_key' => Str::slug(fake()->unique()->words(2, true)),
            'label' => fake()->sentence(2),
            'pattern_type' => 'prefix',
            'match_rules' => [
                [
                    'type' => 'prefix',
                    'value' => '/'.fake()->slug(),
                ],
            ],
            'is_active' => true,
        ];
    }
}
