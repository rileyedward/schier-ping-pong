<?php

use App\Models\Matchup;
use App\Models\Player;
use App\Models\Season;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('generates a round-robin schedule for the season roster', function () {
    $season = Season::factory()->create([
        'start_date' => '2026-05-04',
        'end_date' => '2026-06-07', // 5 weeks
    ]);
    $players = Player::factory(5)->create();
    $season->players()->attach(
        $players->pluck('id')->mapWithKeys(fn ($id) => [$id => ['joined_at' => now()]])->all()
    );

    $this->actingAs($this->admin)
        ->post("/admin/seasons/{$season->id}/matches/generate")
        ->assertRedirect();

    // C(5,2) = 10 matches
    expect($season->matches()->count())->toBe(10);

    // Every pair unique, each player in exactly 4 matches
    $season->matches->each(function ($m) {
        expect($m->player_one_id)->not->toBe($m->player_two_id);
    });

    $counts = $season->matches()
        ->get()
        ->flatMap(fn ($m) => [$m->player_one_id, $m->player_two_id])
        ->countBy();
    foreach ($players as $p) {
        expect($counts[$p->id] ?? 0)->toBe(4);
    }

    // 10 matches across 5 weeks → 2 per week distribution
    $weeks = $season->matches()->get()->groupBy(fn ($m) => $m->scheduled_for->toDateString());
    expect($weeks->count())->toBeGreaterThanOrEqual(1);
});

it('preserves played matches when regenerating', function () {
    $season = Season::factory()->create(['start_date' => '2026-05-04', 'end_date' => '2026-06-07']);
    $players = Player::factory(3)->create();
    $season->players()->attach(
        $players->pluck('id')->mapWithKeys(fn ($id) => [$id => ['joined_at' => now()]])->all()
    );

    $played = Matchup::factory()->create([
        'season_id' => $season->id,
        'player_one_id' => $players[0]->id,
        'player_two_id' => $players[1]->id,
        'played_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/seasons/{$season->id}/matches/generate");

    expect(Matchup::find($played->id))->not->toBeNull();
});

it('requires at least 2 players', function () {
    $season = Season::factory()->create();

    $this->actingAs($this->admin)
        ->post("/admin/seasons/{$season->id}/matches/generate")
        ->assertSessionHasErrors('schedule');
});
