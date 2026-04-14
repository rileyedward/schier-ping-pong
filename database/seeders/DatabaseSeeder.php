<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Player;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Schier Admin',
            'email' => 'admin@schier.test',
            'password' => Hash::make('password'),
        ]);

        $a = League::factory()->create(['name' => 'A Division', 'skill_level' => 'Advanced']);
        $b = League::factory()->create(['name' => 'B Division', 'skill_level' => 'Intermediate']);

        $players = Player::factory(8)->create();
        $a->players()->attach($players->take(4)->pluck('id'), ['joined_at' => now()]);
        $b->players()->attach($players->slice(4)->pluck('id'), ['joined_at' => now()]);
    }
}
