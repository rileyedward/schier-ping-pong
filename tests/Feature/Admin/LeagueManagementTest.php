<?php

use App\Models\League;
use App\Models\Player;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('creates a league', function () {
    $this->actingAs($this->admin)->post('/admin/leagues', [
        'name' => 'Rec League',
        'description' => null,
        'skill_level' => 'Beginner',
    ])->assertRedirect('/admin/leagues');

    expect(League::where('name', 'Rec League')->exists())->toBeTrue();
});

it('attaches and detaches players', function () {
    $league = League::factory()->create();
    $player = Player::factory()->create();

    $this->actingAs($this->admin)
        ->post("/admin/leagues/{$league->id}/players", ['player_id' => $player->id]);

    expect($league->players()->count())->toBe(1);

    $this->actingAs($this->admin)
        ->delete("/admin/leagues/{$league->id}/players/{$player->id}");

    expect($league->players()->count())->toBe(0);
});

it('deletes a league', function () {
    $league = League::factory()->create();

    $this->actingAs($this->admin)->delete("/admin/leagues/{$league->id}")->assertRedirect('/admin/leagues');

    expect(League::find($league->id))->toBeNull();
});
