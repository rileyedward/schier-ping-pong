<?php

namespace Database\Seeders;

use App\Actions\ApplyMatchRating;
use App\Models\Matchup;
use Illuminate\Database\Seeder;

class LeagueMatchResultSeeder extends Seeder
{
    public function run(ApplyMatchRating $applyRating): void
    {
        $matches = Matchup::where('type', 'league')
            ->whereNotNull('player_one_id')
            ->whereNotNull('player_two_id')
            ->whereNull('winner_id')
            ->where('scheduled_for', '<=', now())
            ->get();

        foreach ($matches as $match) {
            $bestOf = $match->best_of ?? 3;
            $winTarget = intdiv($bestOf, 2) + 1;
            $gamesPlayed = random_int($winTarget, $bestOf);
            $loserGames = $gamesPlayed - $winTarget;
            $oneWon = random_int(0, 1) === 1;

            $match->update([
                'winner_id' => $oneWon ? $match->player_one_id : $match->player_two_id,
                'games_won_by_one' => $oneWon ? $winTarget : $loserGames,
                'games_won_by_two' => $oneWon ? $loserGames : $winTarget,
                'played_at' => $match->scheduled_for->midDay(),
            ]);

            $applyRating($match->fresh());
        }
    }
}
