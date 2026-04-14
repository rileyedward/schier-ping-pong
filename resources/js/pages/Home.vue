<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { dashboard, login } from '@/routes';
import InputError from '@/components/InputError.vue';

type PlayerSummary = { id: number; name: string; initials: string };
type RecentMatch = {
    id: number;
    type: 'league' | 'friendly';
    played_at: string | null;
    player_one: PlayerSummary | null;
    player_two: PlayerSummary | null;
    team_one: string | null;
    team_two: string | null;
    team_one_id: number | null;
    team_two_id: number | null;
    games_won_by_one: number | null;
    games_won_by_two: number | null;
    winner_id: number | null;
    winner_team_id: number | null;
};
type TopPlayer = {
    id: number;
    first_name: string;
    last_name: string;
    rating: number;
    wins_this_week: number;
};
type Standing = { id: number; name: string; wins: number; losses: number };
type SeasonBlock = {
    id: number;
    name: string;
    format: 'singles' | 'doubles';
    start_date: string | null;
    end_date: string | null;
    standings: Standing[];
};
type LeagueBlock = { id: number; name: string; seasons: SeasonBlock[] };
type UpcomingMatch = {
    id: number;
    scheduled_for: string | null;
    best_of: number;
    player_one_id: number | null;
    player_two_id: number | null;
    player_one: string | null;
    player_two: string | null;
    team_one_id: number | null;
    team_two_id: number | null;
    team_one: string | null;
    team_two: string | null;
    season_label: string | null;
};
type PlayerOption = { id: number; name: string };

const props = defineProps<{
    recentMatches: RecentMatch[];
    topPlayers: TopPlayer[];
    leagues: LeagueBlock[];
    upcomingMatches: UpcomingMatch[];
    allPlayers: PlayerOption[];
}>();

const initials = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((s) => s[0]?.toUpperCase() ?? '')
        .join('');

const activeLeagueId = ref<number | null>(props.leagues[0]?.id ?? null);
const activeSeasonByLeague = ref<Record<number, number>>(
    Object.fromEntries(props.leagues.map((l) => [l.id, l.seasons[0]?.id ?? 0])),
);
const activeLeague = computed(() => props.leagues.find((l) => l.id === activeLeagueId.value) ?? null);
const activeSeason = computed(() => {
    if (!activeLeague.value) return null;
    const sid = activeSeasonByLeague.value[activeLeague.value.id];
    return activeLeague.value.seasons.find((s) => s.id === sid) ?? activeLeague.value.seasons[0] ?? null;
});

type InputMode = 'choose' | 'league' | 'friendly';
const inputMode = ref<InputMode>('choose');

const selectedMatchId = ref<number | null>(null);
const selectedMatch = computed(
    () => props.upcomingMatches.find((m) => m.id === selectedMatchId.value) ?? null,
);
const leagueWinTarget = computed(() =>
    selectedMatch.value ? Math.floor(selectedMatch.value.best_of / 2) + 1 : 2,
);
const leagueGamesOptions = computed(() => {
    if (!selectedMatch.value) return [];
    const out: number[] = [];
    for (let n = leagueWinTarget.value; n <= selectedMatch.value.best_of; n++) out.push(n);
    return out;
});
const leagueForm = useForm({ winner_id: 0, winner_team_id: 0, games_played: 0 });
const selectedMatchIsDoubles = computed(() => !!selectedMatch.value?.team_one_id);

function pickLeagueMatch(id: number) {
    selectedMatchId.value = id;
    const m = props.upcomingMatches.find((x) => x.id === id);
    if (m) {
        if (m.team_one_id) {
            leagueForm.winner_team_id = m.team_one_id;
        } else {
            leagueForm.winner_id = m.player_one_id ?? 0;
        }
        leagueForm.games_played = Math.floor(m.best_of / 2) + 1;
        leagueForm.clearErrors();
    }
}

function submitLeague() {
    if (!selectedMatch.value) return;
    leagueForm.post(`/matches/${selectedMatch.value.id}/result`, {
        preserveScroll: true,
        onSuccess: () => {
            selectedMatchId.value = null;
            inputMode.value = 'choose';
        },
    });
}

const friendlyForm = useForm({
    is_doubles: false,
    player_one_id: 0,
    player_two_id: 0,
    team_one_player_ids: [0, 0] as [number, number],
    team_two_player_ids: [0, 0] as [number, number],
    winner_side: 'one' as 'one' | 'two',
    best_of: 3,
    winner_id: 0,
    games_played: 2,
});
const friendlyWinTarget = computed(() => Math.floor(friendlyForm.best_of / 2) + 1);
const friendlyGamesOptions = computed(() => {
    const out: number[] = [];
    for (let n = friendlyWinTarget.value; n <= friendlyForm.best_of; n++) out.push(n);
    return out;
});

function submitFriendly() {
    friendlyForm.transform((d) => ({ ...d, games_played: Math.max(d.games_played, friendlyWinTarget.value) })).post(
        '/matches/friendly',
        {
            preserveScroll: true,
            onSuccess: () => {
                friendlyForm.reset();
                friendlyForm.best_of = 3;
                friendlyForm.games_played = 2;
                friendlyForm.team_one_player_ids = [0, 0];
                friendlyForm.team_two_player_ids = [0, 0];
                inputMode.value = 'choose';
            },
        },
    );
}
</script>

<template>
    <Head title="Schier Pong" />
    <div class="min-h-screen bg-neutral-50 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
        <header class="border-b bg-white dark:bg-neutral-900">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <h1 class="text-xl font-semibold">Schier Pong</h1>
                <nav class="flex items-center gap-3 text-sm">
                    <Link v-if="$page.props.auth.user" :href="dashboard()" class="underline">Admin</Link>
                    <Link v-else :href="login()" class="underline">Admin login</Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto grid max-w-6xl gap-6 px-6 py-8 lg:grid-cols-2">
            <!-- Latest matches -->
            <Card>
                <CardHeader><CardTitle>Latest matches</CardTitle></CardHeader>
                <CardContent class="space-y-3">
                    <p v-if="!recentMatches.length" class="text-sm text-neutral-500">No matches yet.</p>
                    <div
                        v-for="m in recentMatches"
                        :key="m.id"
                        class="flex items-center justify-between rounded-md border p-3"
                    >
                        <div class="flex items-center gap-3">
                            <template v-if="m.team_one">
                                <span class="text-sm font-medium" :class="m.winner_team_id === m.team_one_id ? 'text-emerald-600 dark:text-emerald-400' : ''">{{ m.team_one }}</span>
                                <span class="text-xs text-neutral-500">vs</span>
                                <span class="text-sm font-medium" :class="m.winner_team_id === m.team_two_id ? 'text-emerald-600 dark:text-emerald-400' : ''">{{ m.team_two }}</span>
                            </template>
                            <template v-else>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-neutral-200 text-xs font-semibold dark:bg-neutral-700"
                                        :class="m.winner_id === m.player_one?.id ? 'ring-2 ring-emerald-500' : ''"
                                    >
                                        {{ m.player_one?.initials ?? '—' }}
                                    </div>
                                    <span class="text-sm">{{ m.player_one?.name ?? '—' }}</span>
                                </div>
                                <span class="text-xs text-neutral-500">vs</span>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-neutral-200 text-xs font-semibold dark:bg-neutral-700"
                                        :class="m.winner_id === m.player_two?.id ? 'ring-2 ring-emerald-500' : ''"
                                    >
                                        {{ m.player_two?.initials ?? '—' }}
                                    </div>
                                    <span class="text-sm">{{ m.player_two?.name ?? '—' }}</span>
                                </div>
                            </template>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold">
                                {{ m.games_won_by_one }}–{{ m.games_won_by_two }}
                            </div>
                            <div class="text-xs text-neutral-500 capitalize">
                                {{ m.type }}<span v-if="m.played_at"> · {{ m.played_at.slice(0, 10) }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Match input -->
            <Card>
                <CardHeader><CardTitle>Input a match</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <div v-if="inputMode === 'choose'" class="flex gap-2">
                        <Button @click="inputMode = 'league'">League match</Button>
                        <Button variant="outline" @click="inputMode = 'friendly'">Friendly match</Button>
                    </div>

                    <div v-if="inputMode === 'league'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <Label>Pick a scheduled match</Label>
                            <button class="text-xs underline" @click="inputMode = 'choose'; selectedMatchId = null">
                                Back
                            </button>
                        </div>
                        <select
                            v-model.number="selectedMatchId"
                            class="flex h-9 w-full rounded-md border bg-transparent px-2 text-sm"
                            @change="selectedMatchId !== null && pickLeagueMatch(selectedMatchId)"
                        >
                            <option :value="null">Select a match…</option>
                            <option v-for="m in upcomingMatches" :key="m.id" :value="m.id">
                                {{ m.team_one ? m.team_one + ' vs ' + m.team_two : m.player_one + ' vs ' + m.player_two }}
                                <template v-if="m.season_label"> · {{ m.season_label }}</template>
                            </option>
                        </select>

                        <form v-if="selectedMatch" class="space-y-4" @submit.prevent="submitLeague">
                            <div>
                                <Label>Winner</Label>
                                <div class="mt-2 space-y-2 text-sm">
                                    <template v-if="selectedMatchIsDoubles">
                                        <label class="flex items-center gap-2">
                                            <input type="radio" :value="selectedMatch.team_one_id" v-model="leagueForm.winner_team_id" />
                                            {{ selectedMatch.team_one }}
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="radio" :value="selectedMatch.team_two_id" v-model="leagueForm.winner_team_id" />
                                            {{ selectedMatch.team_two }}
                                        </label>
                                    </template>
                                    <template v-else>
                                        <label class="flex items-center gap-2">
                                            <input type="radio" :value="selectedMatch.player_one_id" v-model="leagueForm.winner_id" />
                                            {{ selectedMatch.player_one }}
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="radio" :value="selectedMatch.player_two_id" v-model="leagueForm.winner_id" />
                                            {{ selectedMatch.player_two }}
                                        </label>
                                    </template>
                                </div>
                                <InputError :message="leagueForm.errors.winner_id" />
                                <InputError :message="leagueForm.errors.winner_team_id" />
                            </div>
                            <div>
                                <Label>In how many games did the winner win?</Label>
                                <select
                                    v-model.number="leagueForm.games_played"
                                    class="mt-1 flex h-9 w-full rounded-md border bg-transparent px-2 text-sm"
                                >
                                    <option v-for="n in leagueGamesOptions" :key="n" :value="n">
                                        {{ n }} games ({{ leagueWinTarget }}–{{ n - leagueWinTarget }})
                                    </option>
                                </select>
                                <InputError :message="leagueForm.errors.games_played" />
                            </div>
                            <Button type="submit" :disabled="leagueForm.processing">Record score</Button>
                        </form>
                    </div>

                    <div v-if="inputMode === 'friendly'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <Label>Friendly match</Label>
                            <button class="text-xs underline" @click="inputMode = 'choose'">Back</button>
                        </div>
                        <form class="space-y-4" @submit.prevent="submitFriendly">
                            <!-- Doubles toggle -->
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="is_doubles" v-model="friendlyForm.is_doubles" class="h-4 w-4" />
                                <Label for="is_doubles" class="cursor-pointer">Doubles match</Label>
                            </div>

                            <!-- Singles player pickers -->
                            <template v-if="!friendlyForm.is_doubles">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <Label>Player 1</Label>
                                        <select
                                            v-model.number="friendlyForm.player_one_id"
                                            class="mt-1 flex h-9 w-full rounded-md border bg-transparent px-2 text-sm"
                                        >
                                            <option :value="0">Select…</option>
                                            <option v-for="p in allPlayers" :key="p.id" :value="p.id">{{ p.name }}</option>
                                        </select>
                                        <InputError :message="friendlyForm.errors.player_one_id" />
                                    </div>
                                    <div>
                                        <Label>Player 2</Label>
                                        <select
                                            v-model.number="friendlyForm.player_two_id"
                                            class="mt-1 flex h-9 w-full rounded-md border bg-transparent px-2 text-sm"
                                        >
                                            <option :value="0">Select…</option>
                                            <option v-for="p in allPlayers" :key="p.id" :value="p.id">{{ p.name }}</option>
                                        </select>
                                        <InputError :message="friendlyForm.errors.player_two_id" />
                                    </div>
                                </div>
                                <div>
                                    <Label>Winner</Label>
                                    <select
                                        v-model.number="friendlyForm.winner_id"
                                        class="mt-1 flex h-9 w-full rounded-md border bg-transparent px-2 text-sm"
                                    >
                                        <option :value="0">Select…</option>
                                        <option v-if="friendlyForm.player_one_id" :value="friendlyForm.player_one_id">
                                            {{ allPlayers.find((p) => p.id === friendlyForm.player_one_id)?.name }}
                                        </option>
                                        <option v-if="friendlyForm.player_two_id" :value="friendlyForm.player_two_id">
                                            {{ allPlayers.find((p) => p.id === friendlyForm.player_two_id)?.name }}
                                        </option>
                                    </select>
                                    <InputError :message="friendlyForm.errors.winner_id" />
                                </div>
                            </template>

                            <!-- Doubles team pickers -->
                            <template v-else>
                                <div>
                                    <div class="mb-1 text-sm font-medium">Team 1</div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <Label class="text-xs">Player A</Label>
                                            <select
                                                v-model.number="friendlyForm.team_one_player_ids[0]"
                                                class="mt-1 flex h-9 w-full rounded-md border bg-transparent px-2 text-sm"
                                            >
                                                <option :value="0">Select…</option>
                                                <option v-for="p in allPlayers" :key="p.id" :value="p.id">{{ p.name }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <Label class="text-xs">Player B</Label>
                                            <select
                                                v-model.number="friendlyForm.team_one_player_ids[1]"
                                                class="mt-1 flex h-9 w-full rounded-md border bg-transparent px-2 text-sm"
                                            >
                                                <option :value="0">Select…</option>
                                                <option v-for="p in allPlayers" :key="p.id" :value="p.id">{{ p.name }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <InputError :message="friendlyForm.errors.team_one_player_ids" />
                                </div>
                                <div>
                                    <div class="mb-1 text-sm font-medium">Team 2</div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <Label class="text-xs">Player A</Label>
                                            <select
                                                v-model.number="friendlyForm.team_two_player_ids[0]"
                                                class="mt-1 flex h-9 w-full rounded-md border bg-transparent px-2 text-sm"
                                            >
                                                <option :value="0">Select…</option>
                                                <option v-for="p in allPlayers" :key="p.id" :value="p.id">{{ p.name }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <Label class="text-xs">Player B</Label>
                                            <select
                                                v-model.number="friendlyForm.team_two_player_ids[1]"
                                                class="mt-1 flex h-9 w-full rounded-md border bg-transparent px-2 text-sm"
                                            >
                                                <option :value="0">Select…</option>
                                                <option v-for="p in allPlayers" :key="p.id" :value="p.id">{{ p.name }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <InputError :message="friendlyForm.errors.team_two_player_ids" />
                                </div>
                                <div>
                                    <Label>Winning team</Label>
                                    <div class="mt-2 space-y-2 text-sm">
                                        <label class="flex items-center gap-2">
                                            <input type="radio" value="one" v-model="friendlyForm.winner_side" />
                                            Team 1
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="radio" value="two" v-model="friendlyForm.winner_side" />
                                            Team 2
                                        </label>
                                    </div>
                                    <InputError :message="friendlyForm.errors.winner_side" />
                                </div>
                            </template>

                            <div>
                                <Label>Best of</Label>
                                <select
                                    v-model.number="friendlyForm.best_of"
                                    class="mt-1 flex h-9 w-full rounded-md border bg-transparent px-2 text-sm"
                                >
                                    <option :value="3">3</option>
                                    <option :value="5">5</option>
                                    <option :value="7">7</option>
                                </select>
                            </div>
                            <div>
                                <Label>In how many games did the winner win?</Label>
                                <select
                                    v-model.number="friendlyForm.games_played"
                                    class="mt-1 flex h-9 w-full rounded-md border bg-transparent px-2 text-sm"
                                >
                                    <option v-for="n in friendlyGamesOptions" :key="n" :value="n">
                                        {{ n }} games ({{ friendlyWinTarget }}–{{ n - friendlyWinTarget }})
                                    </option>
                                </select>
                                <InputError :message="friendlyForm.errors.games_played" />
                            </div>
                            <Button type="submit" :disabled="friendlyForm.processing">Record friendly</Button>
                        </form>
                    </div>
                </CardContent>
            </Card>

            <!-- Top players -->
            <Card>
                <CardHeader>
                    <CardTitle>Top players this week</CardTitle>
                    <div data-slot="card-action">
                        <Link href="/players" class="text-xs text-neutral-500 hover:underline">View all →</Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <p v-if="!topPlayers.length" class="text-sm text-neutral-500">No players yet.</p>
                    <ol class="space-y-2">
                        <li
                            v-for="(p, i) in topPlayers"
                            :key="p.id"
                            class="flex items-center gap-3 rounded-md border p-2"
                        >
                            <span class="w-6 text-right text-sm font-semibold text-neutral-500">{{ i + 1 }}</span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-neutral-200 text-xs font-semibold dark:bg-neutral-700">
                                {{ initials(p.first_name + ' ' + p.last_name) }}
                            </div>
                            <div class="flex-1">
                                <Link :href="`/players/${p.id}`" class="text-sm font-medium hover:underline">
                                    {{ p.first_name }} {{ p.last_name }}
                                </Link>
                                <div class="text-xs text-neutral-500">Rating {{ p.rating }}</div>
                            </div>
                            <span class="text-sm font-semibold">{{ p.wins_this_week }} W</span>
                        </li>
                    </ol>
                </CardContent>
            </Card>

            <!-- Leagues + standings -->
            <Card>
                <CardHeader><CardTitle>Leagues</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <p v-if="!leagues.length" class="text-sm text-neutral-500">No active leagues.</p>
                    <div v-if="leagues.length" class="flex flex-wrap gap-2">
                        <button
                            v-for="l in leagues"
                            :key="l.id"
                            class="rounded-md border px-3 py-1 text-sm"
                            :class="activeLeagueId === l.id ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' : ''"
                            @click="activeLeagueId = l.id"
                        >
                            {{ l.name }}
                        </button>
                    </div>
                    <div v-if="activeLeague" class="space-y-3">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="s in activeLeague.seasons"
                                :key="s.id"
                                class="rounded-md border px-2 py-1 text-xs"
                                :class="activeSeasonByLeague[activeLeague.id] === s.id ? 'bg-neutral-200 dark:bg-neutral-700' : ''"
                                @click="activeSeasonByLeague[activeLeague.id] = s.id"
                            >
                                {{ s.name }}
                            </button>
                        </div>
                        <div v-if="activeSeason">
                            <div class="mb-2 text-xs text-neutral-500">
                                <span v-if="activeSeason.start_date">{{ activeSeason.start_date }}</span>
                                <span v-if="activeSeason.end_date"> → {{ activeSeason.end_date }}</span>
                            </div>
                            <table class="w-full text-sm">
                                <thead class="text-left text-xs text-neutral-500">
                                    <tr>
                                        <th class="py-1">#</th>
                                        <th>{{ activeSeason?.format === 'doubles' ? 'Team' : 'Player' }}</th>
                                        <th class="text-right">W</th>
                                        <th class="text-right">L</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(r, i) in activeSeason.standings" :key="r.id" class="border-t">
                                        <td class="py-1">{{ i + 1 }}</td>
                                        <td>{{ r.name }}</td>
                                        <td class="text-right">{{ r.wins }}</td>
                                        <td class="text-right">{{ r.losses }}</td>
                                    </tr>
                                    <tr v-if="!activeSeason.standings.length">
                                        <td colspan="4" class="py-2 text-center text-xs text-neutral-500">
                                            No standings yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </main>
    </div>
</template>
