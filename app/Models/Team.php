<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['season_id', 'name'])]
class Team extends Model
{
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'team_player');
    }

    public function averageRating(string $column): int
    {
        $ratings = $this->players->map(fn ($p) => (int) $p->{$column});

        return $ratings->isEmpty() ? 1000 : (int) round($ratings->average());
    }
}
