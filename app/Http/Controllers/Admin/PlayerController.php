<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlayerRequest;
use App\Http\Requests\Admin\UpdatePlayerRequest;
use App\Models\League;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Players/Index', [
            'players' => Player::query()
                ->withCount('leagues')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'email']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Players/Form', [
            'player' => null,
            'leagues' => League::orderBy('name')->get(['id', 'name']),
            'selectedLeagueIds' => [],
        ]);
    }

    public function store(StorePlayerRequest $request): RedirectResponse
    {
        $player = Player::create($request->safe()->except('league_ids'));
        $player->leagues()->sync($request->validated('league_ids') ?? []);

        return redirect()->route('admin.players.index')->with('status', 'Player created.');
    }

    public function edit(Player $player): Response
    {
        return Inertia::render('Admin/Players/Form', [
            'player' => $player->only(['id', 'first_name', 'last_name', 'email']),
            'leagues' => League::orderBy('name')->get(['id', 'name']),
            'selectedLeagueIds' => $player->leagues()->pluck('leagues.id'),
        ]);
    }

    public function update(UpdatePlayerRequest $request, Player $player): RedirectResponse
    {
        $data = $request->safe()->except(['league_ids', 'password']);
        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }
        $player->update($data);
        $player->leagues()->sync($request->validated('league_ids') ?? []);

        return redirect()->route('admin.players.index')->with('status', 'Player updated.');
    }

    public function destroy(Player $player): RedirectResponse
    {
        $player->delete();

        return redirect()->route('admin.players.index')->with('status', 'Player deleted.');
    }
}
