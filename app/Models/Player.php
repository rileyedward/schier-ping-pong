<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['first_name', 'last_name', 'email', 'password', 'password_changed_at', 'profile_image_path', 'league_rating', 'friendly_rating'])]
#[Hidden(['password', 'remember_token'])]
class Player extends Authenticatable
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'league_rating' => 'integer',
            'friendly_rating' => 'integer',
        ];
    }

    public function leagues(): BelongsToMany
    {
        return $this->belongsToMany(League::class)->withPivot('joined_at');
    }

    public function seasons(): BelongsToMany
    {
        return $this->belongsToMany(Season::class, 'season_player')->withPivot('joined_at');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
