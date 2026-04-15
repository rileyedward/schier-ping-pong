<?php

namespace App\Models;

use Database\Factories\LeagueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'skill_level', 'color'])]
class League extends Model
{
    /** @use HasFactory<LeagueFactory> */
    use HasFactory;

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class)->withPivot('joined_at');
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }
}
