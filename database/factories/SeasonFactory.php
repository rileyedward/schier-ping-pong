<?php

namespace Database\Factories;

use App\Models\League;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->startOfWeek();

        return [
            'league_id' => League::factory(),
            'name' => 'Season '.fake()->numberBetween(1, 20),
            'description' => fake()->sentence(),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addWeeks(6)->toDateString(),
            'format' => 'singles',
        ];
    }
}
