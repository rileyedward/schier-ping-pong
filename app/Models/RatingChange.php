<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatingChange extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'player_id',
        'matchup_id',
        'type',
        'rating_before',
        'rating_after',
        'delta',
        'created_at',
    ];

    protected $casts = [
        'rating_before' => 'integer',
        'rating_after' => 'integer',
        'delta' => 'integer',
        'created_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function matchup(): BelongsTo
    {
        return $this->belongsTo(Matchup::class);
    }
}
