<?php

namespace App\Models;

use Database\Factories\MatchupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'type', 'season_id', 'player_one_id', 'player_two_id',
    'team_one_id', 'team_two_id',
    'scheduled_for', 'played_at', 'best_of',
    'games_won_by_one', 'games_won_by_two', 'winner_id', 'winner_team_id',
])]
class Matchup extends Model
{
    /** @use HasFactory<MatchupFactory> */
    use HasFactory;

    protected $table = 'matches';

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'played_at' => 'datetime',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function playerOne(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_one_id');
    }

    public function playerTwo(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_two_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'winner_id');
    }

    public function teamOne(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_one_id');
    }

    public function teamTwo(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_two_id');
    }

    public function winnerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    public function isDoubles(): bool
    {
        return $this->team_one_id !== null;
    }
}
