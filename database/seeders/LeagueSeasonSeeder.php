<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Matchup;
use App\Models\Player;
use App\Models\Season;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * TODO: Temporary seeder — replace with real league/season data later
 * (same throwaway pattern as PlayerSeeder).
 */
class LeagueSeasonSeeder extends Seeder
{
    public function run(): void
    {
        $leagues = [
            ['name' => 'Premier',  'color' => '#e6c200', 'skill_level' => 'Top', 'min_rating' => 1200],
            ['name' => 'Pro',      'color' => '#3b8ab0', 'skill_level' => 'High', 'min_rating' => 900],
            ['name' => 'Prestige', 'color' => '#3f9c6b', 'skill_level' => 'Mid', 'min_rating' => 700],
            ['name' => 'Pioneer',  'color' => '#6b7280', 'skill_level' => 'Entry', 'min_rating' => 0],
        ];

        $players = Player::orderByDesc('league_rating')->get();

        foreach ($leagues as $cfg) {
            $league = League::create([
                'name' => $cfg['name'],
                'color' => $cfg['color'],
                'skill_level' => $cfg['skill_level'],
                'description' => $cfg['name'].' league',
            ]);

            $roster = $players->filter(fn ($p) => $p->league_rating >= $cfg['min_rating'])
                ->take(14)
                ->values();

            $players = $players->reject(fn ($p) => $roster->contains('id', $p->id))->values();

            $attach = $roster->mapWithKeys(fn ($p) => [$p->id => ['joined_at' => now()]])->all();
            $league->players()->attach($attach);

            $this->createSeason($league, 'Winter 2026', '2026-01-06', '2026-03-31', $roster);
            $this->createSeason($league, 'Spring 2026', '2026-04-07', '2026-06-30', $roster);
        }
    }

    private function createSeason(League $league, string $name, string $start, string $end, $roster): void
    {
        $season = Season::create([
            'league_id' => $league->id,
            'name' => $name,
            'description' => $name.' · '.$league->name,
            'start_date' => $start,
            'end_date' => $end,
            'format' => 'singles',
        ]);

        $attach = $roster->mapWithKeys(fn ($p) => [$p->id => ['joined_at' => now()]])->all();
        $season->players()->attach($attach);

        $this->generateSchedule($season, $roster->pluck('id')->all());
    }

    private function generateSchedule(Season $season, array $playerIds): void
    {
        if (count($playerIds) < 2) {
            return;
        }

        $pairs = [];
        $n = count($playerIds);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $pairs[] = [$playerIds[$i], $playerIds[$j]];
            }
        }

        $start = Carbon::parse($season->start_date)->startOfDay();
        $end = Carbon::parse($season->end_date)->startOfDay();
        $totalDays = max(1, $start->diffInDays($end) + 1);
        $weeks = max(1, (int) ceil($totalDays / 7));
        $perWeek = (int) ceil(count($pairs) / $weeks);

        $rows = [];
        foreach ($pairs as $index => [$one, $two]) {
            $week = intdiv($index, $perWeek);
            $rows[] = [
                'type' => 'league',
                'season_id' => $season->id,
                'player_one_id' => $one,
                'player_two_id' => $two,
                'scheduled_for' => $start->copy()->addWeeks($week)->toDateString(),
                'best_of' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Matchup::insert($rows);
    }
}
