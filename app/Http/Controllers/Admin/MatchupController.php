<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ApplyMatchRating;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMatchupRequest;
use App\Http\Requests\Admin\UpdateMatchupRequest;
use App\Models\Matchup;
use App\Models\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class MatchupController extends Controller
{
    public function storeForSeason(StoreMatchupRequest $request, Season $season): RedirectResponse
    {
        Matchup::create([
            ...$request->validated(),
            'type' => 'league',
            'season_id' => $season->id,
        ]);

        return back()->with('status', 'Match scheduled.');
    }

    public function update(UpdateMatchupRequest $request, Matchup $matchup): RedirectResponse
    {
        $matchup->update($request->validated());

        return back()->with('status', 'Match updated.');
    }

    public function destroy(Matchup $matchup): RedirectResponse
    {
        $matchup->delete();

        return back()->with('status', 'Match removed.');
    }

    public function recordScore(\Illuminate\Http\Request $request, Matchup $matchup, ApplyMatchRating $applyRating): RedirectResponse
    {
        $winTarget = intdiv($matchup->best_of, 2) + 1;

        $data = $request->validate([
            'winner_id' => ['required', 'integer', \Illuminate\Validation\Rule::in([$matchup->player_one_id, $matchup->player_two_id])],
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

        return back()->with('status', 'Score recorded.');
    }

    public function generateSchedule(Season $season): RedirectResponse
    {
        $playerIds = $season->players()->pluck('players.id')->all();

        if (count($playerIds) < 2) {
            return back()->withErrors(['schedule' => 'Need at least 2 players in the season roster.']);
        }

        $season->matches()->whereNull('played_at')->delete();

        $pairs = [];
        $n = count($playerIds);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $pairs[] = [$playerIds[$i], $playerIds[$j]];
            }
        }

        $start = Carbon::parse($season->start_date)->startOfDay();
        $end = Carbon::parse($season->end_date)->startOfDay();
        $totalDays = max(1, $start->diffInDays($end) + 1);
        $weeks = max(1, (int) ceil($totalDays / 7));
        $perWeek = (int) ceil(count($pairs) / $weeks);

        $rows = [];
        foreach ($pairs as $index => [$one, $two]) {
            $week = intdiv($index, $perWeek);
            $rows[] = [
                'type' => 'league',
                'season_id' => $season->id,
                'player_one_id' => $one,
                'player_two_id' => $two,
                'scheduled_for' => $start->copy()->addWeeks($week)->toDateString(),
                'best_of' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Matchup::insert($rows);

        return back()->with('status', count($rows).' matches scheduled across '.$weeks.' week(s).');
    }
}
