<?php

use App\Models\Matchup;
use App\Models\Player;
use App\Models\RatingChange;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('updates league_rating on both players when league score recorded and leaves friendly_rating untouched', function () {
    [$a, $b] = Player::factory(2)->create();
    $match = Matchup::factory()->create([
        'type' => 'league',
        'player_one_id' => $a->id,
        'player_two_id' => $b->id,
        'best_of' => 3,
    ]);

    $this->actingAs($this->admin)->post("/admin/matches/{$match->id}/score", [
        'winner_id' => $a->id,
        'games_played' => 2,
    ])->assertRedirect();

    $a->refresh();
    $b->refresh();

    expect($a->league_rating)->toBe(1008);
    expect($b->league_rating)->toBe(992);
    expect($a->friendly_rating)->toBe(1000);
    expect($b->friendly_rating)->toBe(1000);

    expect(RatingChange::where('matchup_id', $match->id)->count())->toBe(2);
});

it('updates friendly_rating only when friendly match recorded publicly', function () {
    [$a, $b] = Player::factory(2)->create();

    $this->post('/matches/friendly', [
        'player_one_id' => $a->id,
        'player_two_id' => $b->id,
        'best_of' => 3,
        'winner_id' => $b->id,
        'games_played' => 2,
    ])->assertRedirect();

    $a->refresh();
    $b->refresh();

    expect($b->friendly_rating)->toBe(1008);
    expect($a->friendly_rating)->toBe(992);
    expect($a->league_rating)->toBe(1000);
    expect($b->league_rating)->toBe(1000);
});

it('is idempotent when recordScore fires twice for the same match', function () {
    [$a, $b] = Player::factory(2)->create();
    $match = Matchup::factory()->create([
        'player_one_id' => $a->id,
        'player_two_id' => $b->id,
        'best_of' => 3,
    ]);

    $this->actingAs($this->admin)->post("/admin/matches/{$match->id}/score", [
        'winner_id' => $a->id,
        'games_played' => 2,
    ]);
    $this->actingAs($this->admin)->post("/admin/matches/{$match->id}/score", [
        'winner_id' => $a->id,
        'games_played' => 2,
    ]);

    expect($a->fresh()->league_rating)->toBe(1008);
    expect(RatingChange::where('matchup_id', $match->id)->count())->toBe(2);
});

it('upset swings harder than expected win for the same rating gap', function () {
    $favorite = Player::factory()->create(['league_rating' => 1300]);
    $underdog = Player::factory()->create(['league_rating' => 1200]);

    $match = Matchup::factory()->create([
        'player_one_id' => $favorite->id,
        'player_two_id' => $underdog->id,
        'best_of' => 3,
    ]);

    $this->actingAs($this->admin)->post("/admin/matches/{$match->id}/score", [
        'winner_id' => $underdog->id,
        'games_played' => 2,
    ]);

    $change = RatingChange::where('player_id', $underdog->id)->where('matchup_id', $match->id)->first();
    expect($change->delta)->toBe(20);
});
