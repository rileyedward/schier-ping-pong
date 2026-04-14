<?php

use App\Models\Matchup;
use App\Models\Player;
use App\Models\Season;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('schedules a match in a season', function () {
    $season = Season::factory()->create();
    [$a, $b] = Player::factory(2)->create();

    $this->actingAs($this->admin)->post("/admin/seasons/{$season->id}/matches", [
        'player_one_id' => $a->id,
        'player_two_id' => $b->id,
        'scheduled_for' => '2026-05-01',
        'best_of' => 5,
    ])->assertRedirect();

    $m = Matchup::firstOrFail();
    expect($m->season_id)->toBe($season->id);
    expect($m->type)->toBe('league');
    expect($m->best_of)->toBe(5);
});

it('rejects same player on both sides', function () {
    $season = Season::factory()->create();
    $a = Player::factory()->create();

    $this->actingAs($this->admin)->post("/admin/seasons/{$season->id}/matches", [
        'player_one_id' => $a->id,
        'player_two_id' => $a->id,
        'scheduled_for' => '2026-05-01',
        'best_of' => 3,
    ])->assertSessionHasErrors('player_one_id');
});

it('updates a match', function () {
    $match = Matchup::factory()->create(['best_of' => 3]);

    $this->actingAs($this->admin)->put("/admin/matches/{$match->id}", [
        'player_one_id' => $match->player_one_id,
        'player_two_id' => $match->player_two_id,
        'scheduled_for' => '2026-06-15',
        'best_of' => 5,
    ])->assertRedirect();

    expect($match->fresh()->best_of)->toBe(5);
});

it('deletes a match', function () {
    $match = Matchup::factory()->create();

    $this->actingAs($this->admin)->delete("/admin/matches/{$match->id}")->assertRedirect();

    expect(Matchup::find($match->id))->toBeNull();
});
