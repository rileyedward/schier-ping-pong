<?php

namespace Database\Factories;

use App\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<League>
 */
class LeagueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'skill_level' => fake()->randomElement(['Beginner', 'Intermediate', 'Advanced']),
        ];
    }
}
