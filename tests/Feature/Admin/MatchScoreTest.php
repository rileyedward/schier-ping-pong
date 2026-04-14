<?php

use App\Models\Matchup;
use App\Models\Player;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('records a sweep in a best-of-3', function () {
    [$a, $b] = Player::factory(2)->create();
    $match = Matchup::factory()->create([
        'player_one_id' => $a->id,
        'player_two_id' => $b->id,
        'best_of' => 3,
    ]);

    $this->actingAs($this->admin)->post("/admin/matches/{$match->id}/score", [
        'winner_id' => $a->id,
        'games_played' => 2,
    ])->assertRedirect();

    $match->refresh();
    expect($match->winner_id)->toBe($a->id);
    expect($match->games_won_by_one)->toBe(2);
    expect($match->games_won_by_two)->toBe(0);
    expect($match->played_at)->not->toBeNull();
});

it('records a 3-2 in a best-of-5', function () {
    [$a, $b] = Player::factory(2)->create();
    $match = Matchup::factory()->create([
        'player_one_id' => $a->id,
        'player_two_id' => $b->id,
        'best_of' => 5,
    ]);

    $this->actingAs($this->admin)->post("/admin/matches/{$match->id}/score", [
        'winner_id' => $b->id,
        'games_played' => 5,
    ])->assertRedirect();

    $match->refresh();
    expect($match->winner_id)->toBe($b->id);
    expect($match->games_won_by_one)->toBe(2);
    expect($match->games_won_by_two)->toBe(3);
});

it('rejects games_played outside the valid range', function () {
    [$a, $b] = Player::factory(2)->create();
    $match = Matchup::factory()->create([
        'player_one_id' => $a->id,
        'player_two_id' => $b->id,
        'best_of' => 3,
    ]);

    $this->actingAs($this->admin)->post("/admin/matches/{$match->id}/score", [
        'winner_id' => $a->id,
        'games_played' => 1,
    ])->assertSessionHasErrors('games_played');

    $this->actingAs($this->admin)->post("/admin/matches/{$match->id}/score", [
        'winner_id' => $a->id,
        'games_played' => 4,
    ])->assertSessionHasErrors('games_played');
});

it('rejects a winner who did not play the match', function () {
    [$a, $b, $c] = Player::factory(3)->create();
    $match = Matchup::factory()->create([
        'player_one_id' => $a->id,
        'player_two_id' => $b->id,
        'best_of' => 3,
    ]);

    $this->actingAs($this->admin)->post("/admin/matches/{$match->id}/score", [
        'winner_id' => $c->id,
        'games_played' => 2,
    ])->assertSessionHasErrors('winner_id');
});
