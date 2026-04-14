<?php

use App\Models\League;
use App\Models\Player;
use App\Models\Season;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('creates a season and auto-attaches league players', function () {
    $league = League::factory()->create();
    $players = Player::factory(3)->create();
    $league->players()->attach($players->pluck('id'), ['joined_at' => now()]);

    $this->actingAs($this->admin)->post('/admin/seasons', [
        'league_id' => $league->id,
        'name' => 'Spring 2026',
        'description' => null,
        'start_date' => '2026-04-01',
        'end_date' => '2026-05-31',
        'format' => 'singles',
    ])->assertRedirect();

    $season = Season::where('name', 'Spring 2026')->firstOrFail();
    expect($season->league_id)->toBe($league->id);
    expect($season->players)->toHaveCount(3);
});

it('attaches and detaches players on a season', function () {
    $season = Season::factory()->create();
    $player = Player::factory()->create();

    $this->actingAs($this->admin)
        ->post("/admin/seasons/{$season->id}/players", ['player_id' => $player->id]);
    expect($season->players()->count())->toBe(1);

    $this->actingAs($this->admin)
        ->delete("/admin/seasons/{$season->id}/players/{$player->id}");
    expect($season->players()->count())->toBe(0);
});

it('rejects end date before start date', function () {
    $league = League::factory()->create();

    $this->actingAs($this->admin)->post('/admin/seasons', [
        'league_id' => $league->id,
        'name' => 'Bad',
        'start_date' => '2026-05-01',
        'end_date' => '2026-04-01',
    ])->assertSessionHasErrors('end_date');
});
