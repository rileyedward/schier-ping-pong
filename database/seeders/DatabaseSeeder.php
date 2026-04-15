<?php

namespace Database\Seeders;

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

        $this->call(PlayerSeeder::class);
        $this->call(LeagueSeasonSeeder::class);
        $this->call(LeagueMatchResultSeeder::class);
        $this->call(FriendlyMatchSeeder::class);
    }
}
