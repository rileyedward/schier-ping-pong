<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\Matchup;
use App\Models\Player;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $recent = Matchup::query()
            ->whereNotNull('played_at')
            ->with([
                'playerOne:id,first_name,last_name',
                'playerTwo:id,first_name,last_name',
                'teamOne',
                'teamTwo',
            ])
            ->orderByDesc('played_at')
            ->limit(8)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'played_at' => $m->played_at?->toDateTimeString(),
                'player_one' => $this->playerSummary($m->playerOne),
                'player_two' => $this->playerSummary($m->playerTwo),
                'team_one' => $m->teamOne?->name,
                'team_two' => $m->teamTwo?->name,
                'team_one_id' => $m->team_one_id,
                'team_two_id' => $m->team_two_id,
                'games_won_by_one' => $m->games_won_by_one,
                'games_won_by_two' => $m->games_won_by_two,
                'winner_id' => $m->winner_id,
                'winner_team_id' => $m->winner_team_id,
            ]);

        $weekAgo = now()->subWeek();

        $winCounts = Matchup::query()
            ->whereNotNull('winner_id')
            ->where('played_at', '>=', $weekAgo)
            ->selectRaw('winner_id, COUNT(*) as wins')
            ->groupBy('winner_id')
            ->pluck('wins', 'winner_id');

        $topPlayers = Player::query()
            ->orderByDesc('league_rating')
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'league_rating', 'friendly_rating'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'first_name' => $p->first_name,
                'last_name' => $p->last_name,
                'rating' => $p->league_rating,
                'friendly_rating' => $p->friendly_rating,
                'wins_this_week' => (int) ($winCounts[$p->id] ?? 0),
            ])
            ->values();

        $leagues = League::query()
            ->with(['seasons' => fn ($q) => $q->orderByDesc('start_date')])
            ->orderBy('name')
            ->get()
            ->filter(fn ($l) => $l->seasons->isNotEmpty())
            ->map(function (League $league) {
                return [
                    'id' => $league->id,
                    'name' => $league->name,
                    'seasons' => $league->seasons->map(function ($season) {
                        if ($season->isDoubles()) {
                            $matches = $season->matches()->whereNotNull('winner_team_id')->get();
                            $teams = $season->teams()->with('players:id,first_name,last_name')->get();

                            $standings = $teams->map(function ($team) use ($matches) {
                                $wins = $matches->where('winner_team_id', $team->id)->count();
                                $losses = $matches->filter(
                                    fn ($m) => ($m->team_one_id === $team->id || $m->team_two_id === $team->id) && $m->winner_team_id !== $team->id,
                                )->count();

                                return [
                                    'id' => $team->id,
                                    'name' => $team->name,
                                    'wins' => $wins,
                                    'losses' => $losses,
                                ];
                            })
                                ->sortByDesc(fn ($r) => $r['wins'] - $r['losses'])
                                ->values();
                        } else {
                            $matches = $season->matches()->whereNotNull('winner_id')->get();
                            $players = $season->players()->get(['players.id', 'first_name', 'last_name']);

                            $standings = $players->map(function ($p) use ($matches) {
                                $wins = $matches->where('winner_id', $p->id)->count();
                                $losses = $matches->filter(
                                    fn ($m) => ($m->player_one_id === $p->id || $m->player_two_id === $p->id) && $m->winner_id !== $p->id,
                                )->count();

                                return [
                                    'id' => $p->id,
                                    'name' => trim($p->first_name.' '.$p->last_name),
                                    'wins' => $wins,
                                    'losses' => $losses,
                                ];
                            })
                                ->sortByDesc(fn ($r) => $r['wins'] - $r['losses'])
                                ->values();
                        }

                        return [
                            'id' => $season->id,
                            'name' => $season->name,
                            'format' => $season->format,
                            'start_date' => $season->start_date?->toDateString(),
                            'end_date' => $season->end_date?->toDateString(),
                            'standings' => $standings,
                        ];
                    })->values(),
                ];
            })
            ->values();

        $upcomingMatches = Matchup::query()
            ->whereNull('played_at')
            ->whereNotNull('season_id')
            ->with([
                'playerOne:id,first_name,last_name',
                'playerTwo:id,first_name,last_name',
                'teamOne',
                'teamTwo',
                'season:id,name,league_id',
                'season.league:id,name',
            ])
            ->orderBy('scheduled_for')
            ->limit(50)
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
                'season_label' => $m->season
                    ? trim(($m->season->league->name ?? '').' · '.$m->season->name)
                    : null,
            ]);

        $allPlayers = Player::query()
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => trim($p->first_name.' '.$p->last_name),
            ]);

        return Inertia::render('Home', [
            'recentMatches' => $recent,
            'topPlayers' => $topPlayers,
            'leagues' => $leagues,
            'upcomingMatches' => $upcomingMatches,
            'allPlayers' => $allPlayers,
        ]);
    }

    private function playerSummary(?Player $p): ?array
    {
        if (! $p) {
            return null;
        }

        return [
            'id' => $p->id,
            'name' => trim($p->first_name.' '.$p->last_name),
            'initials' => strtoupper(substr($p->first_name, 0, 1).substr($p->last_name, 0, 1)),
        ];
    }
}
