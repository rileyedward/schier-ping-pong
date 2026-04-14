<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeagueRequest;
use App\Http\Requests\Admin\UpdateLeagueRequest;
use App\Models\League;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeagueController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Leagues/Index', [
            'leagues' => League::query()
                ->withCount('players')
                ->orderBy('name')
                ->get(['id', 'name', 'skill_level']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Leagues/Form', ['league' => null]);
    }

    public function store(StoreLeagueRequest $request): RedirectResponse
    {
        League::create($request->validated());

        return redirect()->route('admin.leagues.index')->with('status', 'League created.');
    }

    public function show(League $league): Response
    {
        return Inertia::render('Admin/Leagues/Show', [
            'league' => $league->only(['id', 'name', 'description', 'skill_level']),
            'members' => $league->players()->orderBy('last_name')->get(['players.id', 'first_name', 'last_name', 'email']),
            'availablePlayers' => Player::query()
                ->whereDoesntHave('leagues', fn ($q) => $q->where('leagues.id', $league->id))
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']),
            'seasons' => $league->seasons()
                ->orderByDesc('start_date')
                ->get(['id', 'name', 'start_date', 'end_date']),
        ]);
    }

    public function edit(League $league): Response
    {
        return Inertia::render('Admin/Leagues/Form', [
            'league' => $league->only(['id', 'name', 'description', 'skill_level']),
        ]);
    }

    public function update(UpdateLeagueRequest $request, League $league): RedirectResponse
    {
        $league->update($request->validated());

        return redirect()->route('admin.leagues.index')->with('status', 'League updated.');
    }

    public function destroy(League $league): RedirectResponse
    {
        $league->delete();

        return redirect()->route('admin.leagues.index')->with('status', 'League deleted.');
    }

    public function attachPlayer(Request $request, League $league): RedirectResponse
    {
        $data = $request->validate(['player_id' => ['required', 'integer', 'exists:players,id']]);
        $league->players()->syncWithoutDetaching([$data['player_id'] => ['joined_at' => now()]]);

        return back()->with('status', 'Player added.');
    }

    public function detachPlayer(League $league, Player $player): RedirectResponse
    {
        $league->players()->detach($player->id);

        return back()->with('status', 'Player removed.');
    }
}
