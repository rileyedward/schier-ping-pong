<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PlayerPicker from '@/components/PlayerPicker.vue';
import { dashboard, login } from '@/routes';

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
type LeagueBlock = { id: number; name: string; color: string | null; seasons: SeasonBlock[] };
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
    if (!activeLeague.value) {
return null;
}

    const sid = activeSeasonByLeague.value[activeLeague.value.id];

    return activeLeague.value.seasons.find((s) => s.id === sid) ?? activeLeague.value.seasons[0] ?? null;
});

type MatchContext = 'league' | 'friendly';
const matchContext = ref<MatchContext>('friendly');
type GameOutcome = 'sweep' | 'game4' | 'game5';
const gameOutcome = ref<GameOutcome>('sweep');
const isDoubles = ref(false);

const winnerOneId = ref<number>(0);
const winnerTwoId = ref<number>(0);
const loserOneId = ref<number>(0);
const loserTwoId = ref<number>(0);

const selectedMatchId = ref<number | null>(null);
const selectedMatch = computed(
    () => props.upcomingMatches.find((m) => m.id === selectedMatchId.value) ?? null,
);

const leagueForm = useForm({ winner_id: 0, winner_team_id: 0, games_played: 0 });
const friendlyForm = useForm({
    is_doubles: false,
    player_one_id: 0,
    player_two_id: 0,
    team_one_player_ids: [0, 0] as [number, number],
    team_two_player_ids: [0, 0] as [number, number],
    winner_side: 'one' as 'one' | 'two',
    best_of: 5,
    winner_id: 0,
    games_played: 3,
});

function gamesForOutcome(o: GameOutcome, bestOf: number) {
    const target = Math.floor(bestOf / 2) + 1;

    if (o === 'sweep') {
return target;
}

    if (o === 'game4') {
return target + 1;
}

    return target + 2;
}

const submitError = ref<string | null>(null);

function submit() {
    submitError.value = null;

    if (matchContext.value === 'league') {
return submitLeague();
}

    return submitFriendly();
}

function submitLeague() {
    if (!selectedMatchId.value) {
        submitError.value = 'Pick a scheduled league match first.';

        return;
    }

    const match = selectedMatch.value!;
    const bestOf = match.best_of;
    const games = gamesForOutcome(gameOutcome.value, bestOf);

    if (games > bestOf) {
        submitError.value = `Best of ${bestOf} — ${gameOutcome.value} not possible.`;

        return;
    }

    if (match.team_one_id) {
        const winningTeam = winnerOneId.value;

        if (!winningTeam) {
            submitError.value = 'Select the winning team.';

            return;
        }

        leagueForm.winner_team_id = winningTeam;
        leagueForm.winner_id = 0;
    } else {
        if (!winnerOneId.value) {
            submitError.value = 'Select the winner.';

            return;
        }

        leagueForm.winner_id = winnerOneId.value;
        leagueForm.winner_team_id = 0;
    }

    leagueForm.games_played = games;

    leagueForm.post(`/matches/${match.id}/result`, {
        preserveScroll: true,
        onSuccess: () => {
            selectedMatchId.value = null;
            winnerOneId.value = 0;
            loserOneId.value = 0;
            gameOutcome.value = 'sweep';
        },
    });
}

function submitFriendly() {
    if (!winnerOneId.value || !loserOneId.value) {
        submitError.value = 'Select both winner and loser.';

        return;
    }

    if (isDoubles.value && (!winnerTwoId.value || !loserTwoId.value)) {
        submitError.value = 'Doubles requires both partners.';

        return;
    }

    friendlyForm.is_doubles = isDoubles.value;
    friendlyForm.best_of = 5;
    friendlyForm.games_played = gamesForOutcome(gameOutcome.value, 5);
    friendlyForm.winner_side = 'one';

    if (isDoubles.value) {
        friendlyForm.team_one_player_ids = [winnerOneId.value, winnerTwoId.value];
        friendlyForm.team_two_player_ids = [loserOneId.value, loserTwoId.value];
        friendlyForm.player_one_id = 0;
        friendlyForm.player_two_id = 0;
        friendlyForm.winner_id = 0;
    } else {
        friendlyForm.player_one_id = winnerOneId.value;
        friendlyForm.player_two_id = loserOneId.value;
        friendlyForm.winner_id = winnerOneId.value;
        friendlyForm.team_one_player_ids = [0, 0];
        friendlyForm.team_two_player_ids = [0, 0];
    }

    friendlyForm.post('/matches/friendly', {
        preserveScroll: true,
        onSuccess: () => {
            winnerOneId.value = 0;
            winnerTwoId.value = 0;
            loserOneId.value = 0;
            loserTwoId.value = 0;
            gameOutcome.value = 'sweep';
        },
    });
}

// Deterministic placeholder color for avatars (TODO: replace with real profile images)
const avatarPalette = ['#3f9c6b', '#3b8ab0', '#2a6b82', '#c97a3a', '#8b5cf6', '#e11d48'];
function avatarColor(id: number | null | undefined) {
    if (!id) {
return '#6b7280';
}

    return avatarPalette[id % avatarPalette.length];
}
</script>

<template>
    <Head title="Schier Pong" />
    <div class="min-h-screen" style="background-color: #f4f1ea">
        <!-- Branded header -->
        <header class="bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
                <div class="flex items-center gap-3">
                    <!-- TODO: replace placeholder with Schier logo asset -->
                    <div class="flex h-12 w-12 items-center justify-center rounded-full" style="background-color: #3f9c6b">
                        <div class="h-6 w-6 rounded-full bg-white"></div>
                    </div>
                    <div class="leading-tight">
                        <div class="text-xl font-bold tracking-wide text-neutral-900">SCHIER</div>
                        <div class="text-sm tracking-widest text-neutral-500">PING PONG</div>
                    </div>
                </div>
                <nav class="flex items-center gap-4 text-sm">
                    <Link href="/players" class="text-neutral-600 hover:underline">Players</Link>
                    <Link v-if="$page.props.auth.user" :href="dashboard()" class="text-neutral-600 hover:underline">Admin</Link>
                    <Link v-else :href="login()" class="text-neutral-600 hover:underline">Admin login</Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-8 space-y-8">
            <!-- LATEST MATCHES -->
            <section>
                <h2 class="mb-4 text-center text-2xl font-semibold tracking-wide" style="color: #3b8ab0">
                    LATEST MATCHES
                </h2>
                <p v-if="!recentMatches.length" class="text-center text-sm text-neutral-500">No matches yet.</p>
                <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <div
                        v-for="m in recentMatches"
                        :key="m.id"
                        class="flex flex-col items-center rounded-lg bg-white p-3 shadow-sm"
                    >
                        <div class="flex w-full items-center justify-around">
                            <!-- TODO: replace placeholder circle with player/team avatar -->
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-full text-xs font-semibold text-white"
                                :style="{ backgroundColor: avatarColor(m.player_one?.id ?? m.team_one_id) }"
                                :class="(m.winner_id && m.winner_id === m.player_one?.id) || (m.winner_team_id && m.winner_team_id === m.team_one_id) ? 'ring-4 ring-emerald-400' : ''"
                            >
                                {{ m.player_one?.initials ?? (m.team_one ? 'T1' : '—') }}
                            </div>
                            <div class="text-lg font-bold text-neutral-800">
                                {{ m.games_won_by_one }}–{{ m.games_won_by_two }}
                            </div>
                            <!-- TODO: replace placeholder circle with player/team avatar -->
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-full text-xs font-semibold text-white"
                                :style="{ backgroundColor: avatarColor(m.player_two?.id ?? m.team_two_id) }"
                                :class="(m.winner_id && m.winner_id === m.player_two?.id) || (m.winner_team_id && m.winner_team_id === m.team_two_id) ? 'ring-4 ring-emerald-400' : ''"
                            >
                                {{ m.player_two?.initials ?? (m.team_two ? 'T2' : '—') }}
                            </div>
                        </div>
                        <div class="mt-2 text-xs capitalize text-neutral-500">
                            {{ m.type }}<span v-if="m.played_at"> · {{ m.played_at.slice(0, 10) }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- MATCH INPUT + PLAYERS panels -->
            <section class="grid gap-4 md:grid-cols-2">
                <!-- MATCH INPUT -->
                <div class="rounded-lg p-6 text-white" style="background-color: #2a6b82">
                    <h2 class="mb-4 text-center text-2xl font-semibold tracking-wide">MATCH INPUT</h2>

                    <!-- Context toggle -->
                    <div class="mb-4 flex justify-center gap-4 text-sm">
                        <label class="flex items-center gap-2">
                            <input type="radio" value="league" v-model="matchContext" />
                            League
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" value="friendly" v-model="matchContext" />
                            Friendly
                        </label>
                    </div>

                    <!-- League: pick scheduled match -->
                    <div v-if="matchContext === 'league'" class="mb-4">
                        <label class="mb-1 block text-sm">Scheduled match</label>
                        <select
                            v-model.number="selectedMatchId"
                            class="h-9 w-full rounded-md bg-white/90 px-2 text-sm text-neutral-900"
                        >
                            <option :value="null">Select a match…</option>
                            <option v-for="m in upcomingMatches" :key="m.id" :value="m.id">
                                {{ m.team_one ? m.team_one + ' vs ' + m.team_two : m.player_one + ' vs ' + m.player_two }}
                                <template v-if="m.season_label"> · {{ m.season_label }}</template>
                            </option>
                        </select>
                    </div>

                    <!-- Doubles toggle (friendly only) -->
                    <div v-if="matchContext === 'friendly'" class="mb-4 flex items-center gap-2">
                        <input id="doubles" type="checkbox" v-model="isDoubles" class="h-4 w-4" />
                        <label for="doubles" class="cursor-pointer text-sm">Doubles match</label>
                    </div>

                    <form @submit.prevent="submit" class="space-y-3 text-sm">
                        <!-- Winner(s) -->
                        <div class="grid grid-cols-[80px_1fr] items-center gap-3">
                            <div class="text-right">Winner(s)</div>
                            <div class="flex gap-2">
                                <select
                                    v-if="matchContext === 'league' && selectedMatch?.team_one_id"
                                    v-model.number="winnerOneId"
                                    class="h-9 flex-1 rounded-md bg-white/90 px-2 text-neutral-900"
                                >
                                    <option :value="0">Team…</option>
                                    <option :value="selectedMatch.team_one_id">{{ selectedMatch.team_one }}</option>
                                    <option :value="selectedMatch.team_two_id">{{ selectedMatch.team_two }}</option>
                                </select>
                                <template v-else-if="matchContext === 'league' && selectedMatch">
                                    <select v-model.number="winnerOneId" class="h-9 flex-1 rounded-md bg-white/90 px-2 text-neutral-900">
                                        <option :value="0">Player…</option>
                                        <option :value="selectedMatch.player_one_id">{{ selectedMatch.player_one }}</option>
                                        <option :value="selectedMatch.player_two_id">{{ selectedMatch.player_two }}</option>
                                    </select>
                                </template>
                                <template v-else>
                                    <div class="flex-1">
                                        <PlayerPicker
                                            v-model="winnerOneId"
                                            :players="allPlayers"
                                            label="Winner"
                                            placeholder="Player 1"
                                            :exclude="[winnerTwoId, loserOneId, loserTwoId].filter(Boolean)"
                                        />
                                    </div>
                                    <div v-if="isDoubles" class="flex-1">
                                        <PlayerPicker
                                            v-model="winnerTwoId"
                                            :players="allPlayers"
                                            label="Winner — partner"
                                            placeholder="Player 2 (doubles)"
                                            :exclude="[winnerOneId, loserOneId, loserTwoId].filter(Boolean)"
                                        />
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Loser(s) -->
                        <div class="grid grid-cols-[80px_1fr] items-center gap-3">
                            <div class="text-right">Loser(s)</div>
                            <div class="flex gap-2">
                                <template v-if="matchContext === 'friendly'">
                                    <div class="flex-1">
                                        <PlayerPicker
                                            v-model="loserOneId"
                                            :players="allPlayers"
                                            label="Loser"
                                            placeholder="Player 1"
                                            :exclude="[winnerOneId, winnerTwoId, loserTwoId].filter(Boolean)"
                                        />
                                    </div>
                                    <div v-if="isDoubles" class="flex-1">
                                        <PlayerPicker
                                            v-model="loserTwoId"
                                            :players="allPlayers"
                                            label="Loser — partner"
                                            placeholder="Player 2 (doubles)"
                                            :exclude="[winnerOneId, winnerTwoId, loserOneId].filter(Boolean)"
                                        />
                                    </div>
                                </template>
                                <div v-else class="flex-1 text-xs text-white/70 italic">
                                    (auto — whichever side isn't the winner)
                                </div>
                            </div>
                        </div>

                        <!-- Game outcome radios -->
                        <div class="flex items-center gap-4 pt-2">
                            <label class="flex items-center gap-1.5">
                                <input type="radio" value="sweep" v-model="gameOutcome" /> Sweep
                            </label>
                            <label class="flex items-center gap-1.5">
                                <input type="radio" value="game4" v-model="gameOutcome" /> Game 4
                            </label>
                            <label class="flex items-center gap-1.5">
                                <input type="radio" value="game5" v-model="gameOutcome" /> Game 5
                            </label>
                        </div>

                        <div v-if="submitError" class="text-xs text-red-200">{{ submitError }}</div>
                        <InputError :message="leagueForm.errors.winner_id" />
                        <InputError :message="leagueForm.errors.winner_team_id" />
                        <InputError :message="leagueForm.errors.games_played" />
                        <InputError :message="friendlyForm.errors.player_one_id" />
                        <InputError :message="friendlyForm.errors.player_two_id" />
                        <InputError :message="friendlyForm.errors.winner_id" />

                        <div class="flex justify-end pt-2">
                            <button
                                type="submit"
                                class="rounded-full bg-white px-6 py-1.5 text-sm font-semibold shadow"
                                style="color: #2a6b82"
                                :disabled="leagueForm.processing || friendlyForm.processing"
                            >
                                Submit
                            </button>
                        </div>
                    </form>
                </div>

                <!-- PLAYERS -->
                <div class="rounded-lg p-6 text-white" style="background-color: #3b8ab0">
                    <h2 class="mb-4 text-center text-2xl font-semibold tracking-wide">PLAYERS</h2>
                    <div class="grid grid-cols-5 gap-3">
                        <Link
                            v-for="p in topPlayers"
                            :key="p.id"
                            :href="`/players/${p.id}`"
                            class="flex flex-col items-center gap-1"
                        >
                            <!-- TODO: replace placeholder circle with player profile image -->
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-full text-xs font-semibold text-white ring-2 ring-white/40"
                                :style="{ backgroundColor: avatarColor(p.id) }"
                            >
                                {{ initials(p.first_name + ' ' + p.last_name) }}
                            </div>
                            <div class="text-[10px] text-white/80 truncate w-14 text-center">{{ p.first_name }}</div>
                        </Link>
                    </div>
                    <div class="mt-6 flex justify-center">
                        <Link
                            href="/players"
                            class="rounded-full bg-white px-6 py-1.5 text-sm font-semibold shadow"
                            style="color: #3b8ab0"
                        >
                            All Player Profiles
                        </Link>
                    </div>
                </div>
            </section>

            <!-- LEAGUE -->
            <section>
                <h2 class="mb-6 text-center text-3xl font-semibold tracking-wide text-neutral-700">LEAGUE</h2>

                <div v-if="!leagues.length" class="text-center text-sm text-neutral-500">No active leagues.</div>

                <div v-else>
                    <!-- Tier buttons (colors from DB) -->
                    <div class="mb-6 flex flex-wrap justify-center gap-4">
                        <button
                            v-for="l in leagues"
                            :key="l.id"
                            class="rounded-md px-6 py-2 text-base font-semibold text-white shadow transition-opacity"
                            :class="activeLeagueId === l.id ? 'opacity-100 ring-2 ring-offset-2 ring-neutral-400' : 'opacity-70 hover:opacity-100'"
                            :style="{ backgroundColor: l.color ?? '#6b7280' }"
                            @click="activeLeagueId = l.id"
                        >
                            {{ l.name }}
                        </button>
                    </div>

                    <div v-if="activeLeague" class="space-y-4">
                        <!-- Season dropdown -->
                        <div class="flex justify-center">
                            <select
                                v-model.number="activeSeasonByLeague[activeLeague.id]"
                                class="h-10 w-56 rounded-md border border-neutral-300 bg-white px-3 text-sm shadow-sm"
                            >
                                <option v-for="s in activeLeague.seasons" :key="s.id" :value="s.id">
                                    {{ s.name }}
                                    <template v-if="s.start_date"> ({{ s.start_date.slice(0, 4) }})</template>
                                </option>
                            </select>
                        </div>

                        <!-- Standings table -->
                        <!-- TODO: add Points, Sweeps, WG4, WG5, Projected Rank columns once those stats are tracked -->
                        <div v-if="activeSeason" class="overflow-hidden rounded-md shadow-sm">
                            <table class="w-full text-sm">
                                <thead class="text-white" :style="{ backgroundColor: activeLeague.color ?? '#3f9c6b' }">
                                    <tr>
                                        <th class="px-3 py-2 text-left w-16">Rank</th>
                                        <th class="px-3 py-2 text-left">{{ activeSeason.format === 'doubles' ? 'Team' : 'Player' }}</th>
                                        <th class="px-3 py-2 text-right w-20">Wins</th>
                                        <th class="px-3 py-2 text-right w-20">Losses</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(r, i) in activeSeason.standings"
                                        :key="r.id"
                                        :class="i % 2 === 0 ? 'bg-white' : ''"
                                        :style="i % 2 === 1 ? { backgroundColor: '#c8e6d0' } : {}"
                                    >
                                        <td class="px-3 py-2 font-semibold text-neutral-700">{{ i + 1 }}</td>
                                        <td class="px-3 py-2">{{ r.name }}</td>
                                        <td class="px-3 py-2 text-right font-semibold text-emerald-700">{{ r.wins }}</td>
                                        <td class="px-3 py-2 text-right text-red-600">{{ r.losses }}</td>
                                    </tr>
                                    <tr v-if="!activeSeason.standings.length">
                                        <td colspan="4" class="py-6 text-center text-sm text-neutral-500">
                                            No standings yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
