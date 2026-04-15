<?php

namespace App\Actions;

use App\Models\Matchup;
use App\Models\Player;
use App\Models\RatingChange;
use App\Models\Team;
use App\Services\Rating\UsattRating;
use Illuminate\Support\Facades\DB;

class ApplyMatchRating
{
    public function __invoke(Matchup $match): void
    {
        if (! $match->winner_id && ! $match->winner_team_id) {
            return;
        }

        if (RatingChange::where('matchup_id', $match->id)->exists()) {
            return;
        }

        if ($match->isDoubles()) {
            $this->applyDoubles($match);
        } else {
            $this->applySingles($match);
        }
    }

    private function applySingles(Matchup $match): void
    {
        $column = $match->type === 'league' ? 'league_rating' : 'friendly_rating';

        $winnerId = $match->winner_id;
        $loserId = $winnerId === $match->player_one_id ? $match->player_two_id : $match->player_one_id;

        DB::transaction(function () use ($match, $winnerId, $loserId, $column) {
            $winner = Player::lockForUpdate()->findOrFail($winnerId);
            $loser = Player::lockForUpdate()->findOrFail($loserId);

            $winnerBefore = (int) $winner->{$column};
            $loserBefore = (int) $loser->{$column};

            $delta = UsattRating::delta($winnerBefore, $loserBefore);

            $winnerAfter = $winnerBefore + $delta;
            $loserAfter = max(0, $loserBefore - $delta);

            $winner->{$column} = $winnerAfter;
            $loser->{$column} = $loserAfter;
            $winner->save();
            $loser->save();

            RatingChange::insert([
                [
                    'player_id' => $winner->id,
                    'matchup_id' => $match->id,
                    'type' => $match->type,
                    'rating_before' => $winnerBefore,
                    'rating_after' => $winnerAfter,
                    'delta' => $delta,
                    'created_at' => $match->played_at ?? now(),
                ],
                [
                    'player_id' => $loser->id,
                    'matchup_id' => $match->id,
                    'type' => $match->type,
                    'rating_before' => $loserBefore,
                    'rating_after' => $loserAfter,
                    'delta' => -$delta,
                    'created_at' => $match->played_at ?? now(),
                ],
            ]);
        });
    }

    private function applyDoubles(Matchup $match): void
    {
        $column = $match->type === 'league' ? 'league_rating' : 'friendly_rating';

        $winnerTeam = Team::with('players')->findOrFail($match->winner_team_id);
        $loserTeamId = $match->winner_team_id === $match->team_one_id
            ? $match->team_two_id
            : $match->team_one_id;
        $loserTeam = Team::with('players')->findOrFail($loserTeamId);

        DB::transaction(function () use ($match, $winnerTeam, $loserTeam, $column) {
            $winnerPlayers = Player::lockForUpdate()
                ->whereIn('id', $winnerTeam->players->pluck('id'))
                ->get();
            $loserPlayers = Player::lockForUpdate()
                ->whereIn('id', $loserTeam->players->pluck('id'))
                ->get();

            $winnerAvg = (int) round($winnerPlayers->avg($column));
            $loserAvg = (int) round($loserPlayers->avg($column));

            $delta = UsattRating::delta($winnerAvg, $loserAvg);

            $ratingChanges = [];

            foreach ($winnerPlayers as $player) {
                $before = (int) $player->{$column};
                $after = $before + $delta;
                $player->{$column} = $after;
                $player->save();
                $ratingChanges[] = [
                    'player_id' => $player->id,
                    'matchup_id' => $match->id,
                    'type' => $match->type,
                    'rating_before' => $before,
                    'rating_after' => $after,
                    'delta' => $delta,
                    'created_at' => $match->played_at ?? now(),
                ];
            }

            foreach ($loserPlayers as $player) {
                $before = (int) $player->{$column};
                $after = max(0, $before - $delta);
                $player->{$column} = $after;
                $player->save();
                $ratingChanges[] = [
                    'player_id' => $player->id,
                    'matchup_id' => $match->id,
                    'type' => $match->type,
                    'rating_before' => $before,
                    'rating_after' => $after,
                    'delta' => -$delta,
                    'created_at' => $match->played_at ?? now(),
                ];
            }

            RatingChange::insert($ratingChanges);
        });
    }
}
