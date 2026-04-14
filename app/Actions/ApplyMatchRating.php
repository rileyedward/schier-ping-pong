<?php

namespace App\Actions;

use App\Models\Matchup;
use App\Models\Player;
use App\Models\RatingChange;
use App\Services\Rating\UsattRating;
use Illuminate\Support\Facades\DB;

class ApplyMatchRating
{
    public function __invoke(Matchup $match): void
    {
        if (! $match->winner_id) {
            return;
        }

        if (RatingChange::where('matchup_id', $match->id)->exists()) {
            return;
        }

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
                    'created_at' => now(),
                ],
                [
                    'player_id' => $loser->id,
                    'matchup_id' => $match->id,
                    'type' => $match->type,
                    'rating_before' => $loserBefore,
                    'rating_after' => $loserAfter,
                    'delta' => -$delta,
                    'created_at' => now(),
                ],
            ]);
        });
    }
}
