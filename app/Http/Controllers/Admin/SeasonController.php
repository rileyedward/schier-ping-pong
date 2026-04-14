<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeasonRequest;
use App\Http\Requests\Admin\UpdateSeasonRequest;
use App\Models\League;
use App\Models\Player;
use App\Models\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeasonController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Seasons/Index', [
            'seasons' => Season::query()
                ->with('league:id,name')
                ->withCount('players')
                ->orderByDesc('start_date')
                ->get(['id', 'league_id', 'name', 'start_date', 'end_date']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Seasons/Form', [
            'season' => null,
            'leagues' => League::orderBy('name')->get(['id', 'name']),
            'preselectedLeagueId' => $request->integer('league_id') ?: null,
        ]);
    }

    public function store(StoreSeasonRequest $request): RedirectResponse
    {
        $season = Season::create($request->validated());

        $playerIds = League::findOrFail($season->league_id)
            ->players()
            ->pluck('players.id');

        $attach = $playerIds->mapWithKeys(fn ($id) => [$id => ['joined_at' => now()]])->all();
        $season->players()->attach($attach);

        return redirect()->route('admin.seasons.show', $season)->with('status', 'Season created.');
    }

    public function show(Season $season): Response
    {
        $season->load('league:id,name');

        return Inertia::render('Admin/Seasons/Show', [
            'season' => [
                'id' => $season->id,
                'name' => $season->name,
                'description' => $season->description,
                'start_date' => $season->start_date?->toDateString(),
                'end_date' => $season->end_date?->toDateString(),
                'format' => $season->format,
                'league' => $season->league,
            ],
            'roster' => $season->players()
                ->orderBy('last_name')
                ->get(['players.id', 'first_name', 'last_name', 'email']),
            'availablePlayers' => Player::query()
                ->whereDoesntHave('seasons', fn ($q) => $q->where('seasons.id', $season->id))
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']),
            'teams' => $season->teams()
                ->with('players:id,first_name,last_name')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'players' => $t->players->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => trim($p->first_name.' '.$p->last_name),
                    ])->values(),
                ]),
            'matches' => $season->matches()
                ->with([
                    'playerOne:id,first_name,last_name',
                    'playerTwo:id,first_name,last_name',
                    'teamOne',
                    'teamTwo',
                ])
                ->orderBy('scheduled_for')
                ->orderBy('id')
                ->get()
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'scheduled_for' => $m->scheduled_for?->toDateString(),
                    'best_of' => $m->best_of,
                    'player_one_id' => $m->player_one_id,
                    'player_two_id' => $m->player_two_id,
                    'player_one' => $m->playerOne ? trim($m->playerOne->first_name.' '.$m->playerOne->last_name) : null,
                    'player_two' => $m->playerTwo ? trim($m->playerTwo->first_name.' '.$m->playerTwo->last_name) : null,
                    'team_one_id' => $m->team_one_id,
                    'team_two_id' => $m->team_two_id,
                    'team_one' => $m->teamOne?->name,
                    'team_two' => $m->teamTwo?->name,
                    'winner_team_id' => $m->winner_team_id,
                    'played_at' => $m->played_at?->toDateTimeString(),
                ]),
        ]);
    }

    public function edit(Season $season): Response
    {
        return Inertia::render('Admin/Seasons/Form', [
            'season' => [
                'id' => $season->id,
                'league_id' => $season->league_id,
                'name' => $season->name,
                'description' => $season->description,
                'start_date' => $season->start_date?->toDateString(),
                'end_date' => $season->end_date?->toDateString(),
                'format' => $season->format,
            ],
            'leagues' => League::orderBy('name')->get(['id', 'name']),
            'preselectedLeagueId' => null,
        ]);
    }

    public function update(UpdateSeasonRequest $request, Season $season): RedirectResponse
    {
        $validated = $request->validated();

        if ($season->format !== $validated['format'] && $season->teams()->exists()) {
            return back()->withErrors(['format' => 'Cannot change format after teams have been created.']);
        }

        $season->update($validated);

        return redirect()->route('admin.seasons.show', $season)->with('status', 'Season updated.');
    }

    public function destroy(Season $season): RedirectResponse
    {
        $season->delete();

        return redirect()->route('admin.seasons.index')->with('status', 'Season deleted.');
    }

    public function attachPlayer(Request $request, Season $season): RedirectResponse
    {
        $data = $request->validate(['player_id' => ['required', 'integer', 'exists:players,id']]);
        $season->players()->syncWithoutDetaching([$data['player_id'] => ['joined_at' => now()]]);

        return back()->with('status', 'Player added to season.');
    }

    public function detachPlayer(Season $season, Player $player): RedirectResponse
    {
        $season->players()->detach($player->id);

        return back()->with('status', 'Player removed from season.');
    }
}
