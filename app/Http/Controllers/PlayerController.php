<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\RatingChange;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(): Response
    {
        $players = Player::query()
            ->with('leagues:id,name')
            ->orderByDesc('league_rating')
            ->get(['id', 'first_name', 'last_name', 'league_rating', 'friendly_rating'])
            ->map(function (Player $p) {
                $changes = RatingChange::where('player_id', $p->id)->get(['type', 'delta']);
                $leagueWins = $changes->where('type', 'league')->where('delta', '>', 0)->count();
                $leagueLosses = $changes->where('type', 'league')->where('delta', '<', 0)->count();
                $friendlyWins = $changes->where('type', 'friendly')->where('delta', '>', 0)->count();
                $friendlyLosses = $changes->where('type', 'friendly')->where('delta', '<', 0)->count();

                return [
                    'id' => $p->id,
                    'name' => trim($p->first_name.' '.$p->last_name),
                    'first_name' => $p->first_name,
                    'last_name' => $p->last_name,
                    'league_rating' => $p->league_rating,
                    'friendly_rating' => $p->friendly_rating,
                    'leagues' => $p->leagues->map(fn ($l) => ['id' => $l->id, 'name' => $l->name])->values(),
                    'league_wins' => $leagueWins,
                    'league_losses' => $leagueLosses,
                    'friendly_wins' => $friendlyWins,
                    'friendly_losses' => $friendlyLosses,
                    'total_wins' => $leagueWins + $friendlyWins,
                    'total_losses' => $leagueLosses + $friendlyLosses,
                ];
            });

        return Inertia::render('Players/Index', [
            'players' => $players,
        ]);
    }

    public function show(Player $player): Response
    {
        $player->load('leagues:id,name');

        $changes = RatingChange::where('player_id', $player->id)
            ->orderBy('created_at')
            ->get(['type', 'delta', 'rating_after', 'created_at']);

        $leagueChanges = $changes->where('type', 'league');
        $friendlyChanges = $changes->where('type', 'friendly');

        $leagueWins = $leagueChanges->where('delta', '>', 0)->count();
        $leagueLosses = $leagueChanges->where('delta', '<', 0)->count();
        $friendlyWins = $friendlyChanges->where('delta', '>', 0)->count();
        $friendlyLosses = $friendlyChanges->where('delta', '<', 0)->count();

        $leagueHistory = $leagueChanges->map(fn ($c) => [
            'date' => $c->created_at->toDateString(),
            'rating' => $c->rating_after,
        ])->values();

        $friendlyHistory = $friendlyChanges->map(fn ($c) => [
            'date' => $c->created_at->toDateString(),
            'rating' => $c->rating_after,
        ])->values();

        return Inertia::render('Players/Show', [
            'player' => [
                'id' => $player->id,
                'name' => trim($player->first_name.' '.$player->last_name),
                'first_name' => $player->first_name,
                'last_name' => $player->last_name,
                'league_rating' => $player->league_rating,
                'friendly_rating' => $player->friendly_rating,
                'leagues' => $player->leagues->map(fn ($l) => ['id' => $l->id, 'name' => $l->name])->values(),
            ],
            'stats' => [
                'league_wins' => $leagueWins,
                'league_losses' => $leagueLosses,
                'friendly_wins' => $friendlyWins,
                'friendly_losses' => $friendlyLosses,
                'total_wins' => $leagueWins + $friendlyWins,
                'total_losses' => $leagueLosses + $friendlyLosses,
            ],
            'leagueHistory' => $leagueHistory,
            'friendlyHistory' => $friendlyHistory,
        ]);
    }
}
