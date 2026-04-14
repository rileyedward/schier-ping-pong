<?php

namespace App\Http\Controllers\Admin;

use App\Models\Matchup;
use Inertia\Inertia;
use Inertia\Response;

class MatchupIndexController
{
    public function __invoke(): Response
    {
        $matches = Matchup::query()
            ->with([
                'playerOne:id,first_name,last_name',
                'playerTwo:id,first_name,last_name',
                'winner:id,first_name,last_name',
                'teamOne:id,name',
                'teamTwo:id,name',
                'winnerTeam:id,name',
                'season:id,name,league_id',
                'season.league:id,name',
            ])
            ->orderByDesc('played_at')
            ->orderByDesc('scheduled_for')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($m) => [
                'id'               => $m->id,
                'type'             => $m->type,
                'is_doubles'       => $m->isDoubles(),
                'played_at'        => $m->played_at?->toDateString(),
                'scheduled_for'    => $m->scheduled_for?->toDateString(),
                'best_of'          => $m->best_of,
                'games_won_by_one' => $m->games_won_by_one,
                'games_won_by_two' => $m->games_won_by_two,
                'player_one'       => $m->playerOne ? ['id' => $m->playerOne->id, 'name' => $m->playerOne->first_name.' '.$m->playerOne->last_name] : null,
                'player_two'       => $m->playerTwo ? ['id' => $m->playerTwo->id, 'name' => $m->playerTwo->first_name.' '.$m->playerTwo->last_name] : null,
                'winner'           => $m->winner    ? ['id' => $m->winner->id,    'name' => $m->winner->first_name.' '.$m->winner->last_name]        : null,
                'team_one'         => $m->teamOne   ? ['id' => $m->teamOne->id,   'name' => $m->teamOne->name]                                        : null,
                'team_two'         => $m->teamTwo   ? ['id' => $m->teamTwo->id,   'name' => $m->teamTwo->name]                                        : null,
                'winner_team'      => $m->winnerTeam? ['id' => $m->winnerTeam->id,'name' => $m->winnerTeam->name]                                     : null,
                'season'           => $m->season    ? ['id' => $m->season->id,    'name' => $m->season->name]                                         : null,
                'league'           => $m->season?->league ? ['id' => $m->season->league->id, 'name' => $m->season->league->name]                      : null,
            ]);

        return Inertia::render('Admin/Matches/Index', [
            'matches' => $matches,
        ]);
    }
}
