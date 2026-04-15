<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type Ref = { id: number; name: string };

type MatchRow = {
    id: number;
    type: 'league' | 'friendly';
    is_doubles: boolean;
    played_at: string | null;
    scheduled_for: string | null;
    best_of: number;
    games_won_by_one: number | null;
    games_won_by_two: number | null;
    player_one: Ref | null;
    player_two: Ref | null;
    winner: Ref | null;
    team_one: Ref | null;
    team_two: Ref | null;
    winner_team: Ref | null;
    season: Ref | null;
    league: Ref | null;
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin/matches' },
            { title: 'Matches', href: '/admin/matches' },
        ],
    },
});

const props = defineProps<{ matches: MatchRow[] }>();

// --- Filters ---
const search = ref('');
const filterType = ref<'all' | 'league' | 'friendly'>('all');
const filterLeagueId = ref<number | ''>('');
const filterSeasonId = ref<number | ''>('');
const filterPlayerId = ref<number | ''>('');

// --- Sort ---
type SortKey = 'date' | 'type' | 'league' | 'season';
const sortKey = ref<SortKey>('date');
const sortDir = ref<'asc' | 'desc'>('desc');

function setSort(key: SortKey) {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'desc';
    }
}

function sortIcon(key: SortKey) {
    if (sortKey.value !== key) return '↕';
    return sortDir.value === 'asc' ? '↑' : '↓';
}

// --- Derived filter option lists ---
const allLeagues = computed(() => {
    const seen = new Map<number, string>();
    for (const m of props.matches) {
        if (m.league) seen.set(m.league.id, m.league.name);
    }
    return [...seen.entries()].map(([id, name]) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name));
});

const allSeasons = computed(() => {
    const seen = new Map<number, string>();
    for (const m of props.matches) {
        if (!m.season) continue;
        if (filterLeagueId.value !== '' && m.league?.id !== filterLeagueId.value) continue;
        seen.set(m.season.id, m.season.name);
    }
    return [...seen.entries()].map(([id, name]) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name));
});

const allPlayers = computed(() => {
    const seen = new Map<number, string>();
    for (const m of props.matches) {
        if (m.is_doubles) continue;
        if (m.player_one) seen.set(m.player_one.id, m.player_one.name);
        if (m.player_two) seen.set(m.player_two.id, m.player_two.name);
    }
    return [...seen.entries()].map(([id, name]) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name));
});

// Reset season when league changes
function onLeagueChange() {
    filterSeasonId.value = '';
    filterPlayerId.value = '';
}

// --- Filtered + sorted list ---
const filtered = computed(() => {
    let list = props.matches;

    if (search.value.trim()) {
        const q = search.value.trim().toLowerCase();
        list = list.filter((m) => {
            const sides = [
                m.player_one?.name,
                m.player_two?.name,
                m.team_one?.name,
                m.team_two?.name,
                m.winner?.name,
                m.winner_team?.name,
                m.league?.name,
                m.season?.name,
            ];
            return sides.some((s) => s?.toLowerCase().includes(q));
        });
    }

    if (filterType.value !== 'all') {
        list = list.filter((m) => m.type === filterType.value);
    }

    if (filterLeagueId.value !== '') {
        list = list.filter((m) => m.league?.id === filterLeagueId.value);
    }

    if (filterSeasonId.value !== '') {
        list = list.filter((m) => m.season?.id === filterSeasonId.value);
    }

    if (filterPlayerId.value !== '') {
        list = list.filter(
            (m) => m.player_one?.id === filterPlayerId.value || m.player_two?.id === filterPlayerId.value,
        );
    }

    return [...list].sort((a, b) => {
        const mul = sortDir.value === 'asc' ? 1 : -1;
        switch (sortKey.value) {
            case 'date': {
                const da = a.played_at ?? a.scheduled_for ?? '';
                const db = b.played_at ?? b.scheduled_for ?? '';
                return mul * da.localeCompare(db);
            }
            case 'type':
                return mul * a.type.localeCompare(b.type);
            case 'league':
                return mul * (a.league?.name ?? '').localeCompare(b.league?.name ?? '');
            case 'season':
                return mul * (a.season?.name ?? '').localeCompare(b.season?.name ?? '');
        }
    });
});

function sideOne(m: MatchRow): string {
    return m.is_doubles ? (m.team_one?.name ?? '—') : (m.player_one?.name ?? '—');
}

function sideTwo(m: MatchRow): string {
    return m.is_doubles ? (m.team_two?.name ?? '—') : (m.player_two?.name ?? '—');
}

function winnerLabel(m: MatchRow): string {
    if (m.is_doubles) return m.winner_team?.name ?? '—';
    return m.winner?.name ?? '—';
}

function dateLabel(m: MatchRow): { text: string; pending: boolean } {
    if (m.played_at) return { text: m.played_at, pending: false };
    if (m.scheduled_for) return { text: m.scheduled_for, pending: true };
    return { text: '—', pending: false };
}
</script>

<template>
    <Head title="Matches" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Card>
            <CardHeader>
                <CardTitle>Matches</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- Filters -->
                <div class="flex flex-wrap gap-3">
                    <Input
                        v-model="search"
                        placeholder="Search players, teams, leagues…"
                        class="w-56"
                    />

                    <select
                        v-model="filterType"
                        class="h-9 rounded-md border bg-transparent px-2 text-sm"
                    >
                        <option value="all">All types</option>
                        <option value="league">League</option>
                        <option value="friendly">Friendly</option>
                    </select>

                    <select
                        v-model.number="filterLeagueId"
                        class="h-9 rounded-md border bg-transparent px-2 text-sm"
                        @change="onLeagueChange"
                    >
                        <option value="">All leagues</option>
                        <option v-for="l in allLeagues" :key="l.id" :value="l.id">{{ l.name }}</option>
                    </select>

                    <select
                        v-model.number="filterSeasonId"
                        class="h-9 rounded-md border bg-transparent px-2 text-sm"
                    >
                        <option value="">All seasons</option>
                        <option v-for="s in allSeasons" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>

                    <select
                        v-model.number="filterPlayerId"
                        class="h-9 rounded-md border bg-transparent px-2 text-sm"
                    >
                        <option value="">All players</option>
                        <option v-for="p in allPlayers" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b text-left text-xs text-muted-foreground">
                            <tr>
                                <th class="py-2 pr-4">
                                    <button class="flex items-center gap-1 hover:text-foreground" @click="setSort('date')">
                                        Date <span>{{ sortIcon('date') }}</span>
                                    </button>
                                </th>
                                <th class="py-2 pr-4">
                                    <button class="flex items-center gap-1 hover:text-foreground" @click="setSort('type')">
                                        Type <span>{{ sortIcon('type') }}</span>
                                    </button>
                                </th>
                                <th class="py-2 pr-4">Format</th>
                                <th class="py-2 pr-4">Side 1</th>
                                <th class="py-2 pr-4">Side 2</th>
                                <th class="py-2 pr-4 text-center">Score</th>
                                <th class="py-2 pr-4">Winner</th>
                                <th class="py-2 pr-4">
                                    <button class="flex items-center gap-1 hover:text-foreground" @click="setSort('league')">
                                        League <span>{{ sortIcon('league') }}</span>
                                    </button>
                                </th>
                                <th class="py-2">
                                    <button class="flex items-center gap-1 hover:text-foreground" @click="setSort('season')">
                                        Season <span>{{ sortIcon('season') }}</span>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="m in filtered" :key="m.id" class="hover:bg-muted/40">
                                <td class="py-2 pr-4 font-mono text-xs">
                                    <span>{{ dateLabel(m).text }}</span>
                                    <span
                                        v-if="dateLabel(m).pending"
                                        class="ml-1 rounded-full bg-yellow-100 px-1.5 py-0.5 text-[10px] text-yellow-700"
                                    >sched</span>
                                </td>
                                <td class="py-2 pr-4 capitalize">{{ m.type }}</td>
                                <td class="py-2 pr-4 text-xs text-muted-foreground">
                                    {{ m.is_doubles ? 'Doubles' : 'Singles' }}
                                </td>
                                <td class="py-2 pr-4 font-medium">
                                    <Link v-if="!m.is_doubles && m.player_one" :href="`/admin/players/${m.player_one.id}/edit`" class="underline">{{ m.player_one.name }}</Link>
                                    <span v-else>{{ sideOne(m) }}</span>
                                </td>
                                <td class="py-2 pr-4 font-medium">
                                    <Link v-if="!m.is_doubles && m.player_two" :href="`/admin/players/${m.player_two.id}/edit`" class="underline">{{ m.player_two.name }}</Link>
                                    <span v-else>{{ sideTwo(m) }}</span>
                                </td>
                                <td class="py-2 pr-4 text-center font-mono">
                                    <span v-if="m.games_won_by_one != null">
                                        {{ m.games_won_by_one }}–{{ m.games_won_by_two }}
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="py-2 pr-4">
                                    <span v-if="winnerLabel(m) !== '—'" class="text-emerald-600">
                                        {{ winnerLabel(m) }}
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="py-2 pr-4 text-xs text-muted-foreground">
                                    <Link v-if="m.league" :href="`/admin/leagues/${m.league.id}`" class="underline">{{ m.league.name }}</Link>
                                    <span v-else>—</span>
                                </td>
                                <td class="py-2 text-xs text-muted-foreground">
                                    <Link v-if="m.season" :href="`/admin/seasons/${m.season.id}`" class="underline">{{ m.season.name }}</Link>
                                    <span v-else>—</span>
                                </td>
                            </tr>
                            <tr v-if="!filtered.length">
                                <td colspan="9" class="py-6 text-center text-muted-foreground">No matches found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-muted-foreground">{{ filtered.length }} of {{ matches.length }} matches</p>
            </CardContent>
        </Card>
    </div>
</template>
