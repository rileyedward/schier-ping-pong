<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function store(Request $request, Season $season): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'player_ids' => ['required', 'array', 'size:2'],
            'player_ids.*' => [
                'integer',
                Rule::exists('season_player', 'player_id')->where('season_id', $season->id),
            ],
        ]);

        $existingPlayerIds = $season->teams()
            ->with('players:id')
            ->get()
            ->flatMap(fn ($t) => $t->players->pluck('id'))
            ->unique();

        foreach ($data['player_ids'] as $pid) {
            if ($existingPlayerIds->contains($pid)) {
                return back()->withErrors(['player_ids' => 'One or more players are already on a team this season.']);
            }
        }

        $team = Team::create(['season_id' => $season->id, 'name' => $data['name']]);
        $team->players()->attach($data['player_ids']);

        return back()->with('status', 'Team created.');
    }

    public function destroy(Season $season, Team $team): RedirectResponse
    {
        abort_if($team->season_id !== $season->id, 403);

        $team->delete();

        return back()->with('status', 'Team removed.');
    }
}
