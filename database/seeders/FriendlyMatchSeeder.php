<?php

namespace Database\Seeders;

use App\Actions\ApplyMatchRating;
use App\Models\Matchup;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Seeder;

/**
 * TODO: Temporary seeder — replace with real match history later.
 */
class FriendlyMatchSeeder extends Seeder
{
    public function run(ApplyMatchRating $applyRating): void
    {
        $players = Player::all();

        if ($players->count() < 4) {
            return;
        }

        for ($i = 0; $i < 20; $i++) {
            $isDoubles = random_int(0, 1) === 1;
            $bestOf = [3, 5, 7][random_int(0, 2)];
            $winTarget = intdiv($bestOf, 2) + 1;
            $gamesPlayed = random_int($winTarget, $bestOf);
            $loserGames = $gamesPlayed - $winTarget;
            $playedAt = now()->subDays(random_int(0, 30))->subHours(random_int(0, 23));

            if ($isDoubles) {
                $picks = $players->random(4)->values();
                $teamOne = Team::create(['season_id' => null, 'name' => 'Team 1']);
                $teamOne->players()->attach([$picks[0]->id, $picks[1]->id]);
                $teamTwo = Team::create(['season_id' => null, 'name' => 'Team 2']);
                $teamTwo->players()->attach([$picks[2]->id, $picks[3]->id]);

                $oneWon = random_int(0, 1) === 1;
                $match = Matchup::create([
                    'type' => 'friendly',
                    'team_one_id' => $teamOne->id,
                    'team_two_id' => $teamTwo->id,
                    'winner_team_id' => $oneWon ? $teamOne->id : $teamTwo->id,
                    'best_of' => $bestOf,
                    'games_won_by_one' => $oneWon ? $winTarget : $loserGames,
                    'games_won_by_two' => $oneWon ? $loserGames : $winTarget,
                    'played_at' => $playedAt,
                ]);
            } else {
                $picks = $players->random(2)->values();
                $oneWon = random_int(0, 1) === 1;
                $match = Matchup::create([
                    'type' => 'friendly',
                    'player_one_id' => $picks[0]->id,
                    'player_two_id' => $picks[1]->id,
                    'winner_id' => $oneWon ? $picks[0]->id : $picks[1]->id,
                    'best_of' => $bestOf,
                    'games_won_by_one' => $oneWon ? $winTarget : $loserGames,
                    'games_won_by_two' => $oneWon ? $loserGames : $winTarget,
                    'played_at' => $playedAt,
                ]);
            }

            $applyRating($match);
        }
    }
}
