<?php

use App\Models\League;
use App\Models\Player;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('redirects unauthenticated users from admin routes', function () {
    $this->get('/admin/players')->assertRedirect('/login');
});

it('lists players', function () {
    Player::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get('/admin/players')
        ->assertInertia(fn ($page) => $page->component('Admin/Players/Index')->has('players', 3));
});

it('creates a player and syncs leagues', function () {
    $league = League::factory()->create();

    $this->actingAs($this->admin)->post('/admin/players', [
        'first_name' => 'Pat',
        'last_name' => 'Smash',
        'email' => 'pat@schier.test',
        'password' => 'secret-pw-1',
        'league_ids' => [$league->id],
    ])->assertRedirect('/admin/players');

    $player = Player::where('email', 'pat@schier.test')->first();
    expect($player)->not->toBeNull();
    expect($player->leagues)->toHaveCount(1);
});

it('updates a player without requiring password', function () {
    $player = Player::factory()->create();
    $originalHash = $player->password;

    $this->actingAs($this->admin)->put("/admin/players/{$player->id}", [
        'first_name' => 'New',
        'last_name' => $player->last_name,
        'email' => $player->email,
        'password' => '',
    ])->assertRedirect('/admin/players');

    $player->refresh();
    expect($player->first_name)->toBe('New');
    expect($player->password)->toBe($originalHash);
});

it('deletes a player', function () {
    $player = Player::factory()->create();

    $this->actingAs($this->admin)->delete("/admin/players/{$player->id}")->assertRedirect('/admin/players');

    expect(Player::find($player->id))->toBeNull();
});
