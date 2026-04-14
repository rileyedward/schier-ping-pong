<?php

namespace Database\Factories;

use App\Models\Matchup;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Matchup>
 */
class MatchupFactory extends Factory
{
    protected $model = Matchup::class;

    public function definition(): array
    {
        return [
            'type' => 'league',
            'season_id' => null,
            'player_one_id' => Player::factory(),
            'player_two_id' => Player::factory(),
            'scheduled_for' => now()->addWeek()->toDateString(),
            'best_of' => 3,
        ];
    }
}
