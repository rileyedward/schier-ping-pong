<?php

namespace App\Http\Controllers;

use App\Actions\ApplyMatchRating;
use App\Models\Matchup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicMatchController extends Controller
{
    public function storeFriendly(Request $request, ApplyMatchRating $applyRating): RedirectResponse
    {
        $data = $request->validate([
            'player_one_id' => ['required', 'integer', 'exists:players,id', 'different:player_two_id'],
            'player_two_id' => ['required', 'integer', 'exists:players,id'],
            'best_of' => ['required', 'integer', 'in:3,5,7'],
            'winner_id' => ['required', 'integer'],
            'games_played' => ['required', 'integer'],
        ]);

        if (! in_array($data['winner_id'], [$data['player_one_id'], $data['player_two_id']], true)) {
            return back()->withErrors(['winner_id' => 'Winner must be one of the two players.']);
        }

        $winTarget = intdiv($data['best_of'], 2) + 1;
        if ($data['games_played'] < $winTarget || $data['games_played'] > $data['best_of']) {
            return back()->withErrors(['games_played' => 'Games played out of range.']);
        }

        $loserGames = $data['games_played'] - $winTarget;
        $oneWon = $data['winner_id'] === $data['player_one_id'];

        $match = Matchup::create([
            'type' => 'friendly',
            'player_one_id' => $data['player_one_id'],
            'player_two_id' => $data['player_two_id'],
            'best_of' => $data['best_of'],
            'winner_id' => $data['winner_id'],
            'games_won_by_one' => $oneWon ? $winTarget : $loserGames,
            'games_won_by_two' => $oneWon ? $loserGames : $winTarget,
            'played_at' => now(),
        ]);

        $applyRating($match);

        return back()->with('status', 'Friendly match recorded.');
    }

    public function storeLeagueResult(Request $request, Matchup $matchup, ApplyMatchRating $applyRating): RedirectResponse
    {
        $winTarget = intdiv($matchup->best_of, 2) + 1;

        $data = $request->validate([
            'winner_id' => ['required', 'integer', Rule::in([$matchup->player_one_id, $matchup->player_two_id])],
            'games_played' => ['required', 'integer', 'min:'.$winTarget, 'max:'.$matchup->best_of],
        ]);

        $loserGames = $data['games_played'] - $winTarget;
        $oneWon = $data['winner_id'] === $matchup->player_one_id;

        $matchup->update([
            'winner_id' => $data['winner_id'],
            'games_won_by_one' => $oneWon ? $winTarget : $loserGames,
            'games_won_by_two' => $oneWon ? $loserGames : $winTarget,
            'played_at' => now(),
        ]);

        $applyRating($matchup->refresh());

        return back()->with('status', 'Match result recorded.');
    }
}
